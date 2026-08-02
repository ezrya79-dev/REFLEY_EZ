<?php

use App\Enums\FeatureStatus;
use App\Models\FeatureRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('the board renders its status columns for any authenticated user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/roadmap')
        ->assertOk()
        ->assertSee(__('roadmap.statusProposed'))
        ->assertSee(__('roadmap.statusAccepted'))
        ->assertSee(__('roadmap.statusInProgress'))
        ->assertSee(__('roadmap.statusShipped'));
});

test('guests are redirected to login', function () {
    $this->get('/roadmap')->assertRedirect('/login');
});

test('any member can propose an idea in a known category', function () {
    $member = User::factory()->create();

    $this->actingAs($member)->post('/roadmap', [
        'title' => 'Export CSV des utilisateurs',
        'description' => 'Pouvoir exporter la liste des comptes.',
        'category' => 'utilisateurs-roles',
    ])->assertRedirect();

    $feature = FeatureRequest::query()->firstOrFail();
    expect($feature->status)->toBe(FeatureStatus::Proposed)
        ->and($feature->user_id)->toBe($member->id)
        ->and($feature->category)->toBe('utilisateurs-roles');
});

test('an unknown category is rejected', function () {
    $this->actingAs(User::factory()->create())->post('/roadmap', [
        'title' => 'Idée',
        'description' => 'Description.',
        'category' => 'pas-un-module',
    ])->assertSessionHasErrors('category');
});

test('the board can be filtered by category', function () {
    FeatureRequest::factory()->create(['title' => 'Idée profil', 'category' => 'profil']);
    FeatureRequest::factory()->create(['title' => 'Idée roadmap', 'category' => 'roadmap']);

    $this->actingAs(User::factory()->create())
        ->get('/roadmap?categorie=profil')
        ->assertSee('Idée profil')
        ->assertDontSee('Idée roadmap');
});

test('voting toggles: one click adds, the next removes', function () {
    $user = User::factory()->create();
    $feature = FeatureRequest::factory()->create();

    $this->actingAs($user)->post('/roadmap/'.$feature->id.'/vote');
    expect($feature->votes()->count())->toBe(1);

    $this->actingAs($user)->post('/roadmap/'.$feature->id.'/vote');
    expect($feature->votes()->count())->toBe(0);
});

test('double voting is impossible at the database level', function () {
    $user = User::factory()->create();
    $feature = FeatureRequest::factory()->create();

    $feature->votes()->create(['user_id' => $user->id]);
    $feature->votes()->create(['user_id' => $user->id]);
})->throws(UniqueConstraintViolationException::class);

test('anyone can comment', function () {
    $user = User::factory()->create();
    $feature = FeatureRequest::factory()->create();

    $this->actingAs($user)->post('/roadmap/'.$feature->id.'/commentaires', [
        'body' => 'Très bonne idée.',
    ])->assertRedirect('/roadmap/'.$feature->id);

    expect($feature->comments()->count())->toBe(1);
});

test('arbitration is forbidden to managers and members', function () {
    $feature = FeatureRequest::factory()->create();

    foreach ([User::factory()->manager()->create(), User::factory()->create()] as $actor) {
        $this->actingAs($actor)->put('/roadmap/'.$feature->id, [
            'status' => 'accepted',
            'priority' => 'high',
            'difficulty' => 'small',
        ])->assertForbidden();
    }

    expect($feature->refresh()->status)->toBe(FeatureStatus::Proposed);
});

test('an admin can arbitrate and the change is audited', function () {
    $admin = User::factory()->admin()->create();
    $feature = FeatureRequest::factory()->create();

    Illuminate\Support\Facades\Log::shouldReceive('channel')->with('audit')->once()->andReturnSelf();
    Illuminate\Support\Facades\Log::shouldReceive('info')
        ->withArgs(fn (string $message) => $message === 'roadmap.arbitrated')
        ->once();

    $this->actingAs($admin)->put('/roadmap/'.$feature->id, [
        'status' => 'accepted',
        'priority' => 'high',
        'difficulty' => 'medium',
    ])->assertRedirect('/roadmap/'.$feature->id);

    $feature->refresh();
    expect($feature->status)->toBe(FeatureStatus::Accepted)
        ->and($feature->priority->value)->toBe('high')
        ->and($feature->difficulty->value)->toBe('medium');
});

test('an author can delete their own proposed idea', function () {
    $author = User::factory()->create();
    $feature = FeatureRequest::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->delete('/roadmap/'.$feature->id)->assertRedirect('/roadmap');

    expect(FeatureRequest::query()->count())->toBe(0);
});

test('an author cannot delete their idea once arbitrated', function () {
    $author = User::factory()->create();
    $feature = FeatureRequest::factory()
        ->status(FeatureStatus::Accepted)
        ->create(['user_id' => $author->id]);

    $this->actingAs($author)->delete('/roadmap/'.$feature->id)->assertSessionHasErrors('feature');

    expect(FeatureRequest::query()->count())->toBe(1);
});

test('a member cannot delete someone else\'s idea', function () {
    $feature = FeatureRequest::factory()->create();

    $this->actingAs(User::factory()->create())
        ->delete('/roadmap/'.$feature->id)
        ->assertSessionHasErrors('feature');
});

test('deleting an idea cascades votes and comments', function () {
    $admin = User::factory()->admin()->create();
    $feature = FeatureRequest::factory()->create();
    $feature->votes()->create(['user_id' => $admin->id]);
    $feature->comments()->create(['user_id' => $admin->id, 'body' => 'ok']);

    $this->actingAs($admin)->delete('/roadmap/'.$feature->id);

    expect(App\Models\FeatureVote::query()->count())->toBe(0)
        ->and(App\Models\FeatureComment::query()->count())->toBe(0);
});

test('ideas are sorted by votes within a column', function () {
    $users = User::factory()->count(3)->create();
    $small = FeatureRequest::factory()->create(['title' => 'Peu votée']);
    $big = FeatureRequest::factory()->create(['title' => 'Très votée']);

    foreach ($users as $voter) {
        $big->votes()->create(['user_id' => $voter->id]);
    }

    $content = $this->actingAs($users->first())->get('/roadmap')->getContent();

    expect(strpos($content, 'Très votée'))->toBeLessThan(strpos($content, 'Peu votée'));
});

test('declined ideas live in a collapsed section, not a column', function () {
    FeatureRequest::factory()->status(FeatureStatus::Declined)->create(['title' => 'Idée refusée']);

    $this->actingAs(User::factory()->create())
        ->get('/roadmap')
        ->assertSee(__('roadmap.declinedSection'))
        ->assertSee('Idée refusée');
});

test('the arbitration form is hidden from non-managers', function () {
    $feature = FeatureRequest::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/roadmap/'.$feature->id)
        ->assertOk()
        ->assertDontSee(__('roadmap.arbitrationTitle'));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/roadmap/'.$feature->id)
        ->assertSee(__('roadmap.arbitrationTitle'));
});

test('every roadmap enum label is translated in both locales', function () {
    $enums = [
        ...FeatureStatus::cases(),
        ...App\Enums\FeaturePriority::cases(),
        ...App\Enums\FeatureDifficulty::cases(),
    ];

    foreach (['fr', 'en'] as $locale) {
        foreach ($enums as $case) {
            expect(trans($case->labelKey(), [], $locale))->not->toBe($case->labelKey());
        }

        foreach ((array) config('refley.roadmap_categories') as $slug) {
            $key = 'roadmap.cat'.str_replace('-', '', ucwords($slug, '-'));
            expect(trans($key, [], $locale))->not->toBe($key);
        }
    }
});

<?php

use App\Enums\ContentType;
use App\Models\ContentBlock;
use App\Models\User;
use App\Services\ContentService;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admins and managers can open the content screens', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->manager()->create()] as $actor) {
        $this->actingAs($actor)->get('/contenu')->assertOk()->assertSee(__('content.pageAccueil'));
        $this->actingAs($actor)->get('/contenu/accueil')->assertOk()->assertSee('hero.titre');
    }
});

test('members cannot touch content administration', function () {
    $member = User::factory()->create();

    $this->actingAs($member)->get('/contenu')->assertForbidden();
    $this->actingAs($member)->put('/contenu/accueil', [])->assertForbidden();
    $this->actingAs($member)->get('/medias')->assertForbidden();
});

test('an unknown page slug 404s', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/contenu/inexistante')
        ->assertNotFound();
});

test('saving the form updates the declared zones only', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put('/contenu/accueil', [
        'locale' => 'fr',
        'blocks' => [
            'hero.titre' => 'Nouveau titre',
            'zone.inconnue' => 'Ignorée',
        ],
    ])->assertRedirect();

    expect(app(ContentService::class)->get('accueil', 'hero.titre', 'fr'))->toBe('Nouveau titre')
        ->and(ContentBlock::query()->where('key', 'zone.inconnue')->exists())->toBeFalse();
});

test('an image zone accepts a media id and the page renders it', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    $media = app(MediaService::class)->store(UploadedFile::fake()->image('visuel.png', 1200, 600), $admin);

    $this->actingAs($admin)->put('/contenu/accueil', [
        'locale' => 'fr',
        'images' => ['hero.visuel' => $media->id],
    ])->assertRedirect();

    auth()->logout();
    $this->get('/')->assertSee('w960.webp');
});

test('the markdown preview endpoint returns safe html', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson('/contenu/apercu', ['source' => '**ok** <script>x</script>'])
        ->assertOk()
        ->assertJsonPath('html', fn (string $html) => str_contains($html, '<strong>ok</strong>') && ! str_contains($html, '<script>'));
});

test('history lists revisions and revert restores one', function () {
    $admin = User::factory()->admin()->create();
    $content = app(ContentService::class);
    $content->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Première', $admin);
    $content->set('accueil', 'hero.titre', 'fr', ContentType::Text, 'Seconde', $admin);

    $this->actingAs($admin)
        ->get('/contenu/accueil/historique/hero.titre')
        ->assertOk()
        ->assertSee('Première')
        ->assertSee('Seconde');

    $revision = ContentBlock::query()->firstOrFail()->revisions()->firstOrFail();

    $this->actingAs($admin)->post('/contenu/revisions/'.$revision->id)->assertRedirect();

    expect(app(ContentService::class)->get('accueil', 'hero.titre', 'fr'))->toBe('Première');
});

test('the rescan action rebuilds the map', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/contenu/rescan')
        ->assertRedirect('/contenu');

    expect(file_exists(base_path('bootstrap/cache/content-map.php')))->toBeTrue();
});

test('the content:scan command reports the zones', function () {
    $this->artisan('content:scan')
        ->expectsOutputToContain('accueil')
        ->assertSuccessful();
});

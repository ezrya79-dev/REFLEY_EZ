<?php

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('the profile page renders for any authenticated user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/profil')
        ->assertOk()
        ->assertSee(__('profile.passwordTitle'));
});

test('guests cannot reach the profile', function () {
    $this->get('/profil')->assertRedirect('/login');
});

test('password change requires the correct current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/profil')->post('/profil/mot-de-passe', [
        'current_password' => 'not-the-password',
        'password' => 'New-Password-1234',
        'password_confirmation' => 'New-Password-1234',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('password change enforces the shared policy', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profil/mot-de-passe', [
        'current_password' => 'password',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ])->assertSessionHasErrors('password');
});

test('a valid password change is applied and audited', function () {
    $user = User::factory()->create();

    Illuminate\Support\Facades\Log::shouldReceive('channel')
        ->with('auth')
        ->once()
        ->andReturnSelf();
    Illuminate\Support\Facades\Log::shouldReceive('info')
        ->withArgs(fn (string $message, array $context) => $message === 'profile.password_changed'
            && ! str_contains(json_encode($context), 'New-Password'))
        ->once();

    $this->actingAs($user)->post('/profil/mot-de-passe', [
        'current_password' => 'password',
        'password' => 'New-Password-1234',
        'password_confirmation' => 'New-Password-1234',
    ])->assertRedirect('/profil');

    expect(Hash::check('New-Password-1234', $user->refresh()->password))->toBeTrue();
});

test('a user can upload a profile photo and replace it', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profil/photo', [
        'photo' => UploadedFile::fake()->image('me.jpg', 500, 500),
    ])->assertRedirect('/profil');

    $first = $user->refresh()->avatar_path;
    Storage::disk('public')->assertExists($first);

    $this->actingAs($user)->post('/profil/photo', [
        'photo' => UploadedFile::fake()->image('me2.jpg', 500, 500),
    ]);

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($user->refresh()->avatar_path);
});

test('photo uploads are validated server-side', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profil/photo', [
        'photo' => UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('photo');

    $this->actingAs($user)->post('/profil/photo', [
        'photo' => UploadedFile::fake()->image('huge.jpg')->size(3000),
    ])->assertSessionHasErrors('photo');
});

test('a user can delete their photo', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profil/photo', [
        'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
    ]);
    $path = $user->refresh()->avatar_path;

    $this->actingAs($user)->delete('/profil/photo')->assertRedirect('/profil');

    Storage::disk('public')->assertMissing($path);
    expect($user->refresh()->avatar_path)->toBeNull();
});

test('theme and locale preferences persist across sessions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/profil/preferences', [
        'theme' => 'dark',
        'locale' => 'en',
    ])->assertRedirect('/profil');

    expect($user->refresh()->theme)->toBe(Theme::Dark)->and($user->locale)->toBe('en');

    // Simule déconnexion / reconnexion : la préférence vient de la base.
    auth()->logout();
    $this->actingAs(User::query()->findOrFail($user->id))
        ->get('/tableau-de-bord')
        ->assertSee('data-theme="dark"', escape: false);
});

test('the chosen locale translates the shell', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)->get('/profil')->assertSee('My profile');

    $user->update(['locale' => 'fr']);
    $this->actingAs($user)->get('/profil')->assertSee('Mon profil');
});

test('profile endpoints never accept a user id parameter', function () {
    $user = User::factory()->create();
    $victim = User::factory()->create();

    // Un identifiant injecté est ignoré : seule la ligne de l'acteur change.
    $this->actingAs($user)->post('/profil/preferences', [
        'user_id' => $victim->id,
        'id' => $victim->id,
        'theme' => 'dark',
        'locale' => 'en',
    ]);

    expect($victim->refresh()->theme)->toBe(Theme::System)
        ->and($user->refresh()->theme)->toBe(Theme::Dark);
});

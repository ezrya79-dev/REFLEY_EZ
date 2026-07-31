<?php

use App\Models\User;
use Symfony\Component\Finder\Finder;

test('no hex color leaks outside the design system', function () {
    $offenders = [];

    foreach (Finder::create()->files()->in(resource_path('views'))->name('*.blade.php') as $file) {
        if (preg_match('/#[0-9a-fA-F]{3,6}\b/', $file->getContents(), $match)) {
            // Seuls les placeholders de champ (ex. "#0f766e" en exemple de
            // saisie) sont tolérés ; toute couleur stylée doit être un jeton.
            if (! str_contains($file->getContents(), 'placeholder="'.$match[0].'"')) {
                $offenders[] = $file->getRelativePathname().' → '.$match[0];
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the shell renders in light theme', function () {
    $user = User::factory()->create(['theme' => 'light']);

    $this->actingAs($user)->get('/tableau-de-bord')
        ->assertOk()
        ->assertSee('data-theme="light"', escape: false);
});

test('the shell renders in dark theme', function () {
    $user = User::factory()->create(['theme' => 'dark']);

    $this->actingAs($user)->get('/tableau-de-bord')
        ->assertOk()
        ->assertSee('data-theme="dark"', escape: false);
});

test('system theme emits no data-theme attribute and defers to the media query', function () {
    $user = User::factory()->create(['theme' => 'system']);

    $response = $this->actingAs($user)->get('/tableau-de-bord');

    $response->assertOk();
    expect($response->getContent())->not->toContain('data-theme=');
});

test('the accent preset is exposed on the html element', function () {
    $this->actingAs(User::factory()->create())
        ->get('/tableau-de-bord')
        ->assertSee('data-accent="teal"', escape: false);
});

test('the login page renders through the guest layout', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('guest-shell')
        ->assertSee(__('auth.submit'));
});

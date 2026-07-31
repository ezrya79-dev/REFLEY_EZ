<?php

use App\Enums\Permission;
use App\Models\User;

/*
 * Pour chaque permission : le rôle autorisé passe, le rôle interdit reçoit
 * 403. L'admin passe partout via Gate::before.
 */

$protectedRoutes = [
    'users screen' => ['/utilisateurs', Permission::ManageUsers],
    'settings screen' => ['/reglages', Permission::ManageSettings],
];

foreach ($protectedRoutes as $label => [$uri, $permission]) {
    test("admin can open the {$label}", function () use ($uri) {
        $this->actingAs(User::factory()->admin()->create())->get($uri)->assertOk();
    });

    test("manager is forbidden from the {$label}", function () use ($uri) {
        $this->actingAs(User::factory()->manager()->create())->get($uri)->assertForbidden();
    });

    test("member is forbidden from the {$label}", function () use ($uri) {
        $this->actingAs(User::factory()->create())->get($uri)->assertForbidden();
    });
}

test('gates answer straight from the role matrix', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->manager()->create();
    $member = User::factory()->create();

    foreach (Permission::cases() as $permission) {
        expect($admin->can($permission->value))->toBeTrue();
    }

    expect($manager->can(Permission::ViewMetrics->value))->toBeTrue()
        ->and($manager->can(Permission::ManageUsers->value))->toBeFalse()
        ->and($manager->can(Permission::ManageSettings->value))->toBeFalse()
        ->and($member->can(Permission::ViewMetrics->value))->toBeFalse()
        ->and($member->can(Permission::ManageUsers->value))->toBeFalse();
});

test('the dashboard shows metrics only with metrics.view', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get('/tableau-de-bord')
        ->assertOk()
        ->assertSee(__('ui.metricsUsersTotal'));

    $this->actingAs(User::factory()->create())
        ->get('/tableau-de-bord')
        ->assertOk()
        ->assertDontSee(__('ui.metricsUsersTotal'));
});

test('navigation links follow permissions', function () {
    $this->actingAs(User::factory()->create())
        ->get('/tableau-de-bord')
        ->assertDontSee('/utilisateurs')
        ->assertDontSee('/reglages');

    $this->actingAs(User::factory()->admin()->create())
        ->get('/tableau-de-bord')
        ->assertSee('/utilisateurs')
        ->assertSee('/reglages');
});

<?php

use App\Enums\UserRole;
use App\Models\User;

test('an admin can create a user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/utilisateurs', [
        'name' => 'Nadia Bernard',
        'email' => 'nadia@example.com',
        'password' => 'Valid-Password-1234',
        'role' => UserRole::Manager->value,
        'is_active' => '1',
    ])->assertRedirect('/utilisateurs');

    $created = User::query()->where('email', 'nadia@example.com')->firstOrFail();
    expect($created->role)->toBe(UserRole::Manager)->and($created->is_active)->toBeTrue();
});

test('the shared password policy applies at creation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/utilisateurs', [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'short',
        'role' => UserRole::Member->value,
    ])->assertSessionHasErrors('password');
});

test('an admin can edit role and activation', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->put('/utilisateurs/'.$user->id, [
        'name' => $user->name,
        'email' => $user->email,
        'role' => UserRole::Manager->value,
        'is_active' => '0',
    ])->assertRedirect('/utilisateurs');

    expect($user->refresh()->role)->toBe(UserRole::Manager)->and($user->is_active)->toBeFalse();
});

test('an admin cannot demote themselves (anti-lockout)', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    $this->actingAs($admin)->from('/utilisateurs/'.$admin->id)->put('/utilisateurs/'.$admin->id, [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::Member->value,
        'is_active' => '1',
    ])->assertSessionHasErrors('role');

    expect($admin->refresh()->role)->toBe(UserRole::Admin);
});

test('the last active admin cannot be deactivated (anti-lockout)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put('/utilisateurs/'.$admin->id, [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::Admin->value,
        'is_active' => '0',
    ])->assertSessionHasErrors('role');

    expect($admin->refresh()->is_active)->toBeTrue();
});

test('an admin cannot delete their own account (anti-lockout)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete('/utilisateurs/'.$admin->id)->assertSessionHasErrors('user');

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('an admin can delete another user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->delete('/utilisateurs/'.$user->id)->assertRedirect('/utilisateurs');

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

test('managers and members cannot touch user administration', function () {
    $target = User::factory()->create();

    foreach ([User::factory()->manager()->create(), User::factory()->create()] as $actor) {
        $this->actingAs($actor)->get('/utilisateurs')->assertForbidden();
        $this->actingAs($actor)->post('/utilisateurs', [])->assertForbidden();
        $this->actingAs($actor)->put('/utilisateurs/'.$target->id, [])->assertForbidden();
        $this->actingAs($actor)->delete('/utilisateurs/'.$target->id)->assertForbidden();
    }
});

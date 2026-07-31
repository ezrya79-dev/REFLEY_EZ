<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(UserService::class);
});

function updatePayload(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'is_active' => $user->is_active,
    ], $overrides);
}

test('an admin can demote another admin when one remains', function () {
    $actor = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();

    $this->service->update($actor, $target, updatePayload($target, ['role' => UserRole::Member]));

    expect($target->refresh()->role)->toBe(UserRole::Member);
});

test('demoting the last active admin is refused', function () {
    $actor = User::factory()->admin()->create();
    $other = User::factory()->admin()->inactive()->create();

    $this->service->update($actor, $actor, updatePayload($actor, ['role' => UserRole::Member]));
})->throws(ValidationException::class);

test('deactivating the last active admin is refused', function () {
    $admin = User::factory()->admin()->create();

    $this->service->update($admin, $admin, updatePayload($admin, ['is_active' => false]));
})->throws(ValidationException::class);

test('an admin cannot remove their own admin access even when another admin exists', function () {
    $actor = User::factory()->admin()->create();
    User::factory()->admin()->create();

    $this->service->update($actor, $actor, updatePayload($actor, ['role' => UserRole::Manager]));
})->throws(ValidationException::class);

test('deleting the last active admin is refused', function () {
    $actor = User::factory()->admin()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();

    // Le seul autre admin est inactif : supprimer un admin actif verrouillerait l'app.
    $this->service->delete($inactiveAdmin, $actor);
})->throws(ValidationException::class);

test('self-deletion is refused', function () {
    $actor = User::factory()->admin()->create();

    $this->service->delete($actor, $actor);
})->throws(ValidationException::class);

test('deleting a regular user works', function () {
    $actor = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->service->delete($actor, $target);

    expect(User::query()->whereKey($target->getKey())->exists())->toBeFalse();
});

test('password is only rewritten when provided', function () {
    $actor = User::factory()->admin()->create();
    $target = User::factory()->create();
    $originalHash = $target->password;

    $this->service->update($actor, $target, updatePayload($target, ['password' => null]));
    expect($target->refresh()->password)->toBe($originalHash);

    $this->service->update($actor, $target, updatePayload($target, ['password' => 'New-Password-1234']));
    expect($target->refresh()->password)->not->toBe($originalHash);
});

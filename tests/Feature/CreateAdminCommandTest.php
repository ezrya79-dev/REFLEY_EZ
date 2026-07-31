<?php

use App\Enums\UserRole;
use App\Models\User;

test('refley:admin creates an active administrator', function () {
    $this->artisan('refley:admin', [
        '--name' => 'Prime Admin',
        '--email' => 'prime@refley.fr',
        '--password' => 'First-Admin-Password-1',
    ])->assertSuccessful();

    $admin = User::query()->where('email', 'prime@refley.fr')->firstOrFail();
    expect($admin->role)->toBe(UserRole::Admin)->and($admin->is_active)->toBeTrue();
});

test('refley:admin enforces the shared password policy', function () {
    $this->artisan('refley:admin', [
        '--name' => 'Weak Admin',
        '--email' => 'weak@refley.fr',
        '--password' => 'weak',
    ])->assertFailed();

    expect(User::query()->where('email', 'weak@refley.fr')->exists())->toBeFalse();
});

test('refley:admin refuses duplicate emails', function () {
    User::factory()->create(['email' => 'taken@refley.fr']);

    $this->artisan('refley:admin', [
        '--name' => 'Dup',
        '--email' => 'taken@refley.fr',
        '--password' => 'Valid-Password-1234',
    ])->assertFailed();
});

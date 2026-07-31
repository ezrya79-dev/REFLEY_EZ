<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('the demo seeder provisions one account per role', function () {
    $this->seed(DemoSeeder::class);

    expect(User::query()->where('email', 'admin@demo.test')->firstOrFail()->role)->toBe(UserRole::Admin)
        ->and(User::query()->where('email', 'manager@demo.test')->firstOrFail()->role)->toBe(UserRole::Manager)
        ->and(User::query()->where('email', 'membre@demo.test')->firstOrFail()->role)->toBe(UserRole::Member);
});

test('the demo seeder is idempotent', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(User::query()->count())->toBe(3);
});

test('the demo seeder refuses to run in production', function () {
    $this->app['env'] = 'production';

    try {
        (new DemoSeeder)->run();
        $this->fail('DemoSeeder should have refused to run in production.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('production');
    } finally {
        $this->app['env'] = 'testing';
    }

    expect(User::query()->count())->toBe(0);
});

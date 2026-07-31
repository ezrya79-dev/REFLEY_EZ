<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Jeu de données fictif pour le développement et les tests E2E.
 * NE S'EXÉCUTE JAMAIS EN PRODUCTION : garde-fou en tête de run().
 * Les données réelles ont leurs propres seeders explicites et rejouables.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DemoSeeder must never run in production.');
        }

        User::query()->firstOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Alice Martin',
                'password' => 'Demo-Password-1234',
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'manager@demo.test'],
            [
                'name' => 'Bruno Lefèvre',
                'password' => 'Demo-Password-1234',
                'role' => UserRole::Manager,
                'is_active' => true,
            ],
        );

        User::query()->firstOrCreate(
            ['email' => 'membre@demo.test'],
            [
                'name' => 'Chloé Dubois',
                'password' => 'Demo-Password-1234',
                'role' => UserRole::Member,
                'is_active' => true,
            ],
        );
    }
}

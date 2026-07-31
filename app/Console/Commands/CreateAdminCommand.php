<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crée le premier administrateur — la base démarre vide en production et
 * l'écran d'administration des comptes exige déjà d'être administrateur.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'refley:admin
        {--name= : Nom complet}
        {--email= : Adresse e-mail}
        {--password= : Mot de passe (sinon demandé de façon masquée)}';

    protected $description = 'Créer un compte administrateur';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?? '') ?: (string) $this->ask('Nom complet');
        $email = (string) ($this->option('email') ?? '') ?: (string) $this->ask('Adresse e-mail');
        $password = (string) ($this->option('password') ?? '') ?: (string) $this->secret('Mot de passe');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::defaults()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->info("Administrateur créé : {$user->email}");

        return self::SUCCESS;
    }
}

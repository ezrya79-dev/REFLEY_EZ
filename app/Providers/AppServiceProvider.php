<?php

namespace App\Providers;

use App\Contracts\SmtpProbe;
use App\Enums\Permission;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\SocketSmtpProbe;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(\App\Services\ContentService::class);
        $this->app->bind(SmtpProbe::class, SocketSmtpProbe::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGates();
        $this->configurePasswordPolicy();
    }

    /**
     * Un Gate par permission atomique ; l'admin reçoit tout via Gate::before.
     * Dans les contrôleurs / vues : Gate::authorize(Permission::X->value),
     * $user->can(Permission::X->value), @can(...) — JAMAIS de comparaison
     * de rôle en dur.
     */
    private function registerGates(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->isAdmin() ? true : null;
        });

        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                fn (User $user): bool => $user->role->hasPermission($permission),
            );
        }
    }

    /**
     * Politique de mot de passe unique, partagée par la création de compte,
     * l'administration des utilisateurs et le changement depuis le profil.
     */
    private function configurePasswordPolicy(): void
    {
        Password::defaults(fn (): Password => Password::min(12)->letters()->mixedCase()->numbers());
    }
}

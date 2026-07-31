<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Member = 'member';

    public function labelKey(): string
    {
        return 'rbac.role'.ucfirst($this->value);
    }

    /** Seul le Gate::before (via User::isAdmin) consulte ce test de rôle. */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Permissions accordées au rôle (source unique de vérité du RBAC).
     * L'administrateur reçoit toutes les permissions via un Gate::before,
     * mais la liste reste exhaustive pour l'affichage et les tests.
     *
     * @return array<int, Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Manager => [
                Permission::ViewMetrics,
                Permission::ViewAudit,
            ],
            self::Member => [],
        };
    }

    public function hasPermission(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}

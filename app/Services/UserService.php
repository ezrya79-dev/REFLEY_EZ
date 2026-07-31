<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Administration des comptes, avec les garde-fous anti-verrouillage : il doit
 * toujours rester au moins un administrateur actif, et personne ne peut
 * retirer son propre accès administrateur. Appliqué ici — côté serveur —
 * et pas seulement dans l'interface.
 */
class UserService
{
    /**
     * @param  array{name: string, email: string, password: string, role: UserRole, is_active: bool}  $data
     */
    public function create(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ]);
    }

    /**
     * @param  array{name: string, email: string, role: UserRole, is_active: bool, password?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function update(User $actor, User $user, array $data): User
    {
        $this->guardAgainstLockout($actor, $user, $data['role'], $data['is_active']);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $data['is_active'],
        ]);

        $password = $data['password'] ?? null;

        if ($password !== null && $password !== '') {
            $user->password = $password;
        }

        $user->save();

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function delete(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('users.errorSelfDelete')]);
        }

        if ($this->isLastActiveAdmin($user)) {
            throw ValidationException::withMessages(['user' => __('users.errorLastAdmin')]);
        }

        (new AvatarService)->delete($user);
        $user->delete();
    }

    /**
     * @throws ValidationException
     */
    private function guardAgainstLockout(User $actor, User $user, UserRole $newRole, bool $newIsActive): void
    {
        $losesAdminAccess = $user->isAdmin() && ($newRole !== UserRole::Admin || ! $newIsActive);

        if (! $losesAdminAccess) {
            return;
        }

        if ($actor->is($user)) {
            throw ValidationException::withMessages(['role' => __('users.errorSelfDemote')]);
        }

        if ($this->isLastActiveAdmin($user)) {
            throw ValidationException::withMessages(['role' => __('users.errorLastAdmin')]);
        }
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin() || ! $user->is_active) {
            return false;
        }

        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->doesntExist();
    }
}

<?php

use App\Enums\Permission;
use App\Enums\UserRole;

test('admin role bundles every permission', function () {
    expect(UserRole::Admin->permissions())->toBe(Permission::cases());
});

test('manager role holds exactly metrics, audit and content editing', function () {
    expect(UserRole::Manager->permissions())->toBe([
        Permission::ViewMetrics,
        Permission::ViewAudit,
        Permission::ManageContent,
        Permission::ManageMedia,
    ]);
});

test('member role holds no elevated permission', function () {
    expect(UserRole::Member->permissions())->toBe([]);
});

test('hasPermission reflects the matrix', function () {
    expect(UserRole::Manager->hasPermission(Permission::ViewMetrics))->toBeTrue()
        ->and(UserRole::Manager->hasPermission(Permission::ManageUsers))->toBeFalse()
        ->and(UserRole::Member->hasPermission(Permission::ViewMetrics))->toBeFalse();
});

test('every role and permission label is translated in both locales', function () {
    foreach (['fr', 'en'] as $locale) {
        foreach (UserRole::cases() as $role) {
            expect(trans($role->labelKey(), [], $locale))->not->toBe($role->labelKey());
        }

        foreach (Permission::cases() as $permission) {
            expect(trans($permission->labelKey(), [], $locale))->not->toBe($permission->labelKey());
        }
    }
});

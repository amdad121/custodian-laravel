<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Tests\Models\CustomPermission;
use AmdadulHaq\Custodian\Tests\Models\CustomRole;
use AmdadulHaq\Custodian\Tests\Models\User;

it('uses configured model classes when resolving roles and permissions', function (): void {
    config()->set('custodian.models.role', CustomRole::class);
    config()->set('custodian.models.permission', CustomPermission::class);
    config()->set('custodian.tables.roles', 'roles');
    config()->set('custodian.tables.permissions', 'permissions');

    $role = CustomRole::query()->create(['name' => 'admin']);
    $permission = CustomPermission::query()->create(['name' => 'users.create']);
    $role->givePermissionTo($permission);

    $user = User::query()->create([
        'name' => 'Custom Model User',
        'email' => 'custom-models@example.com',
        'password' => 'password',
    ]);
    $user->assignRole('admin');

    expect($user->roles()->first())
        ->toBeInstanceOf(CustomRole::class)
        ->and($user->getPermissions()->first())
        ->toBeInstanceOf(CustomPermission::class);
});

it('uses the configured pivot table for the role users relation', function (): void {
    config()->set('custodian.models.role', CustomRole::class);
    config()->set('custodian.tables.roles', 'team_roles');

    $role = new CustomRole;

    expect($role->users()->getTable())->toBe('team_role_user');
});

it('supports runtime role and permission checks with configured models', function (): void {
    config()->set('custodian.models.role', CustomRole::class);
    config()->set('custodian.models.permission', CustomPermission::class);

    $role = CustomRole::query()->create(['name' => 'admin']);
    $permission = CustomPermission::query()->create(['name' => 'users.create']);
    $user = User::query()->create([
        'name' => 'Configured User',
        'email' => 'configured@example.com',
        'password' => 'password',
    ]);

    $role->givePermissionTo($permission);
    $user->assignRole($role);

    expect($user->fresh()->hasRole('admin'))->toBeTrue()
        ->and($user->fresh()->hasPermission('users.create'))->toBeTrue();
});

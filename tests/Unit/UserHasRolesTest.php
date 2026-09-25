<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Exceptions\PermissionDeniedException;
use AmdadulHaq\Custodian\Models\Permission;
use AmdadulHaq\Custodian\Models\Role;
use AmdadulHaq\Custodian\Tests\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function (): void {
    $this->user = User::query()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->role = Role::query()->create(['name' => 'admin']);
    $this->permission = Permission::query()->create(['name' => 'users.create']);
});

it('can assign role to user', function (): void {
    $this->user->assignRole($this->role);

    expect($this->user->roles)
        ->toHaveCount(1)
        ->first()->name->toBe('admin');
});

it('can assign role to user by name', function (): void {
    $this->user->assignRole('admin');

    expect($this->user->roles)
        ->toHaveCount(1)
        ->first()->name->toBe('admin');
});

it('can assign multiple roles to user in one call', function (): void {
    $role2 = Role::query()->create(['name' => 'editor']);
    $role3 = Role::query()->create(['name' => 'moderator']);

    $this->user->assignRole('admin', [$role2, $role3->id]);

    expect($this->user->fresh()->roles->pluck('name')->sort()->values()->all())
        ->toEqual(['admin', 'editor', 'moderator']);
});

it('can sync roles to user', function (): void {
    $role2 = Role::query()->create(['name' => 'editor']);

    $this->user->syncRoles([$this->role->id, $role2->id]);

    expect($this->user->roles)
        ->toHaveCount(2);
});

it('can revoke role from user', function (): void {
    $this->user->assignRole($this->role);
    expect($this->user->roles)->toHaveCount(1);

    $this->user->revokeRole($this->role);

    $this->user = $this->user->fresh()->roles;

    expect($this->user)
        ->toHaveCount(0);
});

it('can revoke all roles from user', function (): void {
    $role2 = Role::query()->create(['name' => 'editor']);
    $this->user->assignRole($this->role, $role2);
    expect($this->user->roles)->toHaveCount(2);

    $this->user->revokeRoles();

    expect($this->user->fresh()->roles)->toHaveCount(0);
});

it('can sync roles without detaching', function (): void {
    $this->user->assignRole($this->role);

    $role2 = Role::query()->create(['name' => 'editor']);
    $this->user->syncRolesWithoutDetaching([$role2->id]);

    expect($this->user->fresh()->roles)
        ->toHaveCount(2)
        ->pluck('name')->sort()->values()->toArray()
        ->toEqual(['admin', 'editor']);
});

it('can check if user has role', function (): void {
    $this->user->assignRole($this->role);

    expect($this->user->hasRole('admin'))
        ->toBeTrue();

    expect($this->user->hasRole('editor'))
        ->toBeFalse();
});

it('can check if user has role by model instance', function (): void {
    $this->user->assignRole($this->role);

    $editor = Role::query()->create(['name' => 'editor']);

    expect($this->user->hasRole($this->role))
        ->toBeTrue()
        ->and($this->user->hasRole($editor))
        ->toBeFalse();
});

it('can check if user has all roles', function (): void {
    $role2 = Role::query()->create(['name' => 'editor']);

    $this->user->syncRoles([$this->role->id, $role2->id]);

    expect($this->user->hasAllRoles(['admin', 'editor']))
        ->toBeTrue();

    expect($this->user->hasAllRoles(['admin', 'moderator']))
        ->toBeFalse();
});

it('can check if user has any role', function (): void {
    $this->user->assignRole($this->role);

    expect($this->user->hasAnyRole(['admin', 'editor']))
        ->toBeTrue();

    expect($this->user->hasAnyRole(['editor', 'moderator']))
        ->toBeFalse();
});

it('can get all user roles', function (): void {
    $role2 = Role::query()->create(['name' => 'editor']);
    $role3 = Role::query()->create(['name' => 'moderator']);

    $this->user->syncRoles([$this->role->id, $role2->id, $role3->id]);

    $this->user->refresh();

    $allRoles = $this->user->roles;

    expect($allRoles)
        ->toHaveCount(3)
        ->pluck('name')->sort()->values()
        ->toArray()
        ->toEqual(['admin', 'editor', 'moderator']);
});

it('can check if user has permission via role', function (): void {
    $this->role->givePermissionTo($this->permission);
    $this->user->assignRole($this->role);

    expect($this->user->hasPermission('users.create'))
        ->toBeTrue();

    expect($this->user->hasPermission('users.delete'))
        ->toBeFalse();
});

it('can get all user permissions from roles', function (): void {
    $permission2 = Permission::query()->create(['name' => 'users.update']);

    $this->role->givePermissionTo($this->permission);
    $role2 = Role::query()->create(['name' => 'editor']);
    $role2->givePermissionTo($permission2);

    $this->user->syncRoles([$this->role->id, $role2->id]);

    $this->user->refresh();

    $permissions = $this->user->getPermissions();

    expect($permissions)
        ->toHaveCount(2)
        ->pluck('name')->sort()->values()
        ->toArray()
        ->toEqual(['users.create', 'users.update']);
});

it('can get all user permission names', function (): void {
    $permission2 = Permission::query()->create(['name' => 'users.update']);
    $permission3 = Permission::query()->create(['name' => 'users.delete']);

    $this->role->givePermissionTo($this->permission);
    $role2 = Role::query()->create(['name' => 'editor']);
    $role2->givePermissionTo($permission2);

    $this->user->syncRoles([$this->role->id, $role2->id]);

    $this->user->refresh();

    $allPermissions = $this->user->roles->flatMap(fn ($role) => $role->permissions->pluck('name'));

    expect($allPermissions)
        ->toHaveCount(2)
        ->sort()->values()
        ->toArray()
        ->toEqual(['users.create', 'users.update']);
});

it('can check wildcard permissions', function (): void {
    $wildcardPermission = Permission::query()->create(['name' => 'users.*']);
    $this->role->givePermissionTo($wildcardPermission);
    $this->user->assignRole($this->role);

    expect($this->user->hasPermission('users.create'))
        ->toBeTrue();

    expect($this->user->hasPermission('users.delete'))
        ->toBeTrue();
});

it('does not match wildcard permissions when disabled', function (): void {
    config()->set('custodian.wildcard.enabled', false);

    $wildcardPermission = Permission::query()->create(['name' => 'users.*']);
    $this->role->givePermissionTo($wildcardPermission);
    $this->user->assignRole($this->role);

    expect($this->user->hasPermission('users.create'))
        ->toBeFalse();
});

it('throws exception when user lacks permission', function (): void {
    $this->role->givePermissionTo($this->permission);
    $this->user->assignRole($this->role);

    throw_if(! $this->user->hasPermission('users.delete'), PermissionDeniedException::create('users.delete'));
})->throws(PermissionDeniedException::class);

it('defines gate for permissions', function (): void {
    $this->role->givePermissionTo($this->permission);
    $this->user->assignRole($this->role);
    $this->user->refresh();

    // Test that permission gate works via user method
    $hasPermission = $this->user->hasPermission('users.create');
    expect($hasPermission)->toBeTrue();
});

it('defines gate for roles', function (): void {
    $this->user->assignRole($this->role);
    $this->user->refresh();

    // Test that role gate works via user method
    $hasRole = $this->user->hasRole('admin');
    expect($hasRole)->toBeTrue();
});

it('can get role labels keyed by name with fallback to name', function (): void {
    $labeled = Role::query()->create(['name' => 'editor', 'label' => 'Content Editor']);
    $unlabeled = Role::query()->create(['name' => 'viewer']);

    $this->user->assignRole($labeled, $unlabeled);

    expect($this->user->getRoleLabels())->toBe([
        'editor' => 'Content Editor',
        'viewer' => 'viewer',
    ]);
});

it('requires every role when hasAllRoles is given a collection', function (): void {
    Role::query()->create(['name' => 'editor']);
    $this->user->assignRole('admin');

    expect($this->user->hasAllRoles(collect(['admin', 'editor'])))->toBeFalse()
        ->and($this->user->hasAllRoles(collect(['admin'])))->toBeTrue();
});

it('returns false from hasAllRoles when given no roles', function (): void {
    $this->user->assignRole('admin');

    expect($this->user->hasAllRoles())->toBeFalse()
        ->and($this->user->hasAllRoles([]))->toBeFalse();
});

it('does not drop zero segments from wildcard permissions', function (): void {
    $this->role->givePermissionTo(Permission::query()->create(['name' => 'api.0.*']));
    $this->user->assignRole($this->role);

    expect($this->user->hasPermission('api.0.read'))->toBeTrue()
        ->and($this->user->hasPermission('api.1.read'))->toBeFalse();
});

it('does not let a dotted wildcard match its bare prefix', function (): void {
    $this->role->givePermissionTo(Permission::query()->create(['name' => 'posts.*']));
    $this->user->assignRole($this->role);

    expect($this->user->hasPermission('posts.edit'))->toBeTrue()
        ->and($this->user->hasPermission('posts'))->toBeFalse()
        ->and($this->user->hasPermission('postsx.edit'))->toBeFalse();
});

it('resolves roles with numeric names by name when no such ID exists', function (): void {
    $numeric = Role::query()->create(['name' => '2024']);

    $this->user->assignRole('2024');

    expect($this->user->hasRole('2024'))->toBeTrue()
        ->and($this->user->roles->pluck('id')->all())->toBe([$numeric->id]);
});

it('does not treat scientific notation as a role ID', function (): void {
    expect(fn () => $this->user->assignRole('1e0'))
        ->toThrow(ModelNotFoundException::class);
});

it('prefers an ID over a numeric name when both match', function (): void {
    Role::query()->create(['name' => (string) $this->role->id]);

    $this->user->assignRole((string) $this->role->id);

    expect($this->user->getRoleNames())->toBe(['admin']);
});

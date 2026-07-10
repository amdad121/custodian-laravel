<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Exceptions\ProtectedRoleException;
use AmdadulHaq\Custodian\Models\Permission;
use AmdadulHaq\Custodian\Models\Role;
use AmdadulHaq\Custodian\Tests\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->role = Role::query()->create([
        'name' => 'admin',
        'label' => 'Administrator',
    ]);

    $this->permission = Permission::query()->create([
        'name' => 'users.create',
        'label' => 'Create Users',
    ]);
});

it('can create a role', function (): void {
    expect($this->role)
        ->name->toBe('admin')
        ->label->toBe('Administrator');

    assertDatabaseHas('roles', [
        'name' => 'admin',
    ]);
});

it('can assign permission to role', function (): void {
    $this->role->givePermissionTo($this->permission);

    expect($this->role->permissions)
        ->toHaveCount(1)
        ->first()->name->toBe('users.create');
});

it('can assign permission to role by name', function (): void {
    $this->role->givePermissionTo('users.create');

    expect($this->role->fresh()->permissions)
        ->toHaveCount(1)
        ->first()->name->toBe('users.create');
});

it('can assign multiple permissions to role in one call', function (): void {
    $permission2 = Permission::query()->create([
        'name' => 'users.update',
        'label' => 'Update Users',
    ]);
    $permission3 = Permission::query()->create([
        'name' => 'users.delete',
        'label' => 'Delete Users',
    ]);

    $this->role->givePermissionTo('users.create', [$permission2, $permission3->id]);

    expect($this->role->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toEqual(['users.create', 'users.delete', 'users.update']);
});

it('can sync permissions to role', function (): void {
    $permission2 = Permission::query()->create([
        'name' => 'users.update',
        'label' => 'Update Users',
    ]);

    $this->role->syncPermissions([$this->permission->id, $permission2->id]);

    expect($this->role->permissions)
        ->toHaveCount(2);

    $this->role->syncPermissions([]);

    expect($this->role->fresh()->permissions)
        ->toHaveCount(0);
});

it('can revoke permission from role', function (): void {
    $this->role->givePermissionTo($this->permission);
    expect($this->role->permissions)->toHaveCount(1);

    $this->role->revokePermissionTo($this->permission);

    expect($this->role->fresh()->permissions)
        ->toHaveCount(0);
});

it('can revoke all permissions from role', function (): void {
    $permission2 = Permission::query()->create(['name' => 'users.update', 'label' => 'Update']);
    $this->role->givePermissionTo($this->permission, $permission2);
    expect($this->role->permissions)->toHaveCount(2);

    $this->role->revokeAllPermissions();

    expect($this->role->fresh()->permissions)->toHaveCount(0);
});

it('throws exception when role does not exist', function (): void {
    Role::query()->where('name', 'non-existent')->firstOrFail();
})->throws(ModelNotFoundException::class);

it('can get all users with a role', function (): void {
    $user1 = User::query()->create([
        'name' => 'User 1',
        'email' => 'user1@example.com',
        'password' => 'password',
    ]);

    $user2 = User::query()->create([
        'name' => 'User 2',
        'email' => 'user2@example.com',
        'password' => 'password',
    ]);

    $user3 = User::query()->create([
        'name' => 'User 3',
        'email' => 'user3@example.com',
        'password' => 'password',
    ]);

    $this->role->users()->attach([$user1->id, $user2->id]);

    $usersWithRole = $this->role->users;

    expect($usersWithRole)
        ->toHaveCount(2)
        ->pluck('name')->sort()->values()
        ->toArray()
        ->toEqual(['User 1', 'User 2']);
});

it('can check if user belongs to role via users relation', function (): void {
    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->role->users()->attach($user->id);

    $hasUser = $this->role->users()->where('id', $user->id)->exists();

    expect($hasUser)->toBeTrue();
});

it('can query protected and unprotected roles via scopes', function (): void {
    Role::query()->create([
        'name' => 'system',
        'is_protected' => true,
    ]);

    $protected = Role::query()->protected()->pluck('name')->all();
    $unprotected = Role::query()->unprotected()->pluck('name')->all();

    expect($protected)->toContain('system')
        ->and($unprotected)->toContain('admin')
        ->and($unprotected)->not->toContain('system');
});

it('can query users with roles via scope', function (): void {
    $admin = User::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $editor = User::query()->create([
        'name' => 'Editor User',
        'email' => 'editor@example.com',
        'password' => 'password',
    ]);

    $editorRole = Role::query()->create(['name' => 'editor']);

    $admin->assignRole($this->role);
    $editor->assignRole($editorRole);

    expect(User::query()->withRoles('admin')->pluck('email')->all())
        ->toEqual(['admin@example.com']);
});

it('prevents deleting a protected role', function (): void {
    $role = Role::query()->create(['name' => 'super-admin', 'is_protected' => true]);

    expect(fn () => $role->delete())
        ->toThrow(ProtectedRoleException::class);

    expect(Role::query()->whereKey($role->getKey())->exists())->toBeTrue();
});

it('throws when assigning a role name that does not exist', function (): void {
    $user = User::query()->create([
        'name' => 'Strict User',
        'email' => 'strict@example.com',
        'password' => 'password',
    ]);

    expect(fn () => $user->assignRole('nonexistent-role'))
        ->toThrow(ModelNotFoundException::class);
});

it('throws when revoking a role name that does not exist and keeps existing roles', function (): void {
    $user = User::query()->create([
        'name' => 'Revoke User',
        'email' => 'revoke-strict@example.com',
        'password' => 'password',
    ]);
    $user->assignRole($this->role);

    expect(fn () => $user->revokeRole('nonexistent-role'))
        ->toThrow(ModelNotFoundException::class);

    expect($user->fresh()->hasRole($this->role->getName()))->toBeTrue();
});

it('reflects role changes immediately on the same instance without refresh', function (): void {
    $user = User::query()->create([
        'name' => 'Fresh User',
        'email' => 'fresh-state@example.com',
        'password' => 'password',
    ]);

    expect($user->hasRole($this->role->getName()))->toBeFalse();

    $user->assignRole($this->role);
    expect($user->hasRole($this->role->getName()))->toBeTrue();

    $user->revokeRole($this->role);
    expect($user->hasRole($this->role->getName()))->toBeFalse();
});

it('exposes label and description accessors', function (): void {
    $role = Role::query()->create([
        'name' => 'support',
        'label' => 'Support Agent',
        'description' => 'Handles customer tickets',
    ]);

    expect($role->getLabel())->toBe('Support Agent')
        ->and($role->getDescription())->toBe('Handles customer tickets')
        ->and(Role::query()->create(['name' => 'bare'])->getLabel())->toBeNull();
});

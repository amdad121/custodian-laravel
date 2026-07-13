<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Events\PermissionGranted;
use AmdadulHaq\Custodian\Events\PermissionRevoked;
use AmdadulHaq\Custodian\Events\RoleAssigned;
use AmdadulHaq\Custodian\Events\RoleRevoked;
use AmdadulHaq\Custodian\Models\Permission;
use AmdadulHaq\Custodian\Models\Role;
use AmdadulHaq\Custodian\Tests\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->user = User::query()->create([
        'name' => 'Event User',
        'email' => 'event-user@example.com',
        'password' => 'password',
    ]);
    $this->role = Role::query()->create(['name' => 'editor']);
    $this->permission = Permission::query()->create(['name' => 'posts.edit']);
});

it('dispatches RoleAssigned when a role is assigned', function (): void {
    Event::fake();

    $this->user->assignRole($this->role);

    Event::assertDispatched(RoleAssigned::class, fn (RoleAssigned $event): bool => $event->subject->is($this->user) && in_array($this->role->id, $event->roleIds, true));
});

it('dispatches RoleAssigned when roles are synced', function (): void {
    Event::fake();

    $this->user->syncRoles([$this->role->id]);

    Event::assertDispatched(RoleAssigned::class);
});

it('dispatches RoleRevoked when a role is revoked', function (): void {
    $this->user->assignRole($this->role);
    Event::fake();

    $this->user->revokeRole($this->role);

    Event::assertDispatched(RoleRevoked::class, fn (RoleRevoked $event): bool => $event->subject->is($this->user) && $event->role?->is($this->role));
});

it('dispatches RoleRevoked when all roles are revoked', function (): void {
    $this->user->assignRole($this->role);
    Event::fake();

    $this->user->revokeRoles();

    Event::assertDispatched(RoleRevoked::class, fn (RoleRevoked $event): bool => ! $event->role instanceof Model);
});

it('dispatches PermissionGranted when a permission is given to a role', function (): void {
    Event::fake();

    $this->role->givePermissionTo($this->permission);

    Event::assertDispatched(PermissionGranted::class, fn (PermissionGranted $event): bool => $event->role->is($this->role) && in_array($this->permission->id, $event->permissionIds, true));
});

it('dispatches PermissionRevoked when a permission is revoked from a role', function (): void {
    $this->role->givePermissionTo($this->permission);
    Event::fake();

    $this->role->revokePermissionTo($this->permission);

    Event::assertDispatched(PermissionRevoked::class, fn (PermissionRevoked $event): bool => $event->role->is($this->role) && $event->permission?->is($this->permission));
});

it('dispatches RoleRevoked when syncing roles detaches an existing role', function (): void {
    $role2 = Role::query()->create(['name' => 'author']);
    $this->user->assignRole($this->role, $role2);
    Event::fake();

    $this->user->syncRoles([$role2->id]);

    Event::assertDispatched(RoleRevoked::class);
});

it('does not dispatch RoleRevoked when syncing roles detaches nothing', function (): void {
    $this->user->assignRole($this->role);
    Event::fake();

    $this->user->syncRoles([$this->role->id]);

    Event::assertNotDispatched(RoleRevoked::class);
});

it('dispatches PermissionRevoked when syncing permissions detaches an existing permission', function (): void {
    $permission2 = Permission::query()->create(['name' => 'posts.delete']);
    $this->role->givePermissionTo($this->permission, $permission2);
    Event::fake();

    $this->role->syncPermissions([$permission2->id]);

    Event::assertDispatched(PermissionRevoked::class);
});

it('does not dispatch PermissionRevoked when syncing permissions detaches nothing', function (): void {
    $this->role->givePermissionTo($this->permission);
    Event::fake();

    $this->role->syncPermissions([$this->permission->id]);

    Event::assertNotDispatched(PermissionRevoked::class);
});

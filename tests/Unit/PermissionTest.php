<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Enums\PermissionType;
use AmdadulHaq\Custodian\Models\Permission;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->permission = Permission::query()->create([
        'name' => 'users.create',
        'label' => 'Create Users',
    ]);
});

it('can create a permission', function (): void {
    expect($this->permission)
        ->name->toBe('users.create')
        ->label->toBe('Create Users');

    assertDatabaseHas('permissions', [
        'name' => 'users.create',
    ]);
});

it('can check if permission is wildcard', function (): void {
    $wildcardPermission = Permission::query()->create([
        'name' => 'posts.*',
        'label' => 'All Posts Permissions',
    ]);

    expect($wildcardPermission->isWildcard())
        ->toBeTrue();

    expect($this->permission->isWildcard())
        ->toBeFalse();
});

it('recalculates is_wildcard when the name is renamed', function (): void {
    expect($this->permission->is_wildcard)->toBeFalse();

    $this->permission->update(['name' => 'users.*']);

    expect($this->permission->fresh()->is_wildcard)->toBeTrue();

    $this->permission->update(['name' => 'users.create']);

    expect($this->permission->fresh()->is_wildcard)->toBeFalse();
});

it('can get permission group', function (): void {
    expect($this->permission->getGroup())
        ->toBe('users');
});

it('can get permission type', function (): void {
    $permission = Permission::query()->create([
        'name' => 'posts.create',
        'label' => 'Create Posts',
    ]);

    expect($permission->getType())
        ->toBe(PermissionType::CREATE);
});

it('throws exception when permission does not exist', function (): void {
    Permission::query()->where('name', 'non-existent')->firstOrFail();
})->throws(ModelNotFoundException::class);

it('can query wildcard permissions via scope', function (): void {
    Permission::query()->create([
        'name' => 'posts.*',
        'label' => 'All Posts Permissions',
    ]);

    expect(Permission::query()->wildcard()->pluck('name')->all())
        ->toEqual(['posts.*']);
});

it('can query permissions by group via scope', function (): void {
    Permission::query()->create([
        'name' => 'users.delete',
        'label' => 'Delete Users',
    ]);

    Permission::query()->create([
        'name' => 'posts.create',
        'label' => 'Create Posts',
    ]);

    expect(Permission::query()->byGroup('users')->pluck('name')->sort()->values()->all())
        ->toEqual(['users.create', 'users.delete']);
});

it('treats underscores in byGroup as literal characters', function (): void {
    Permission::query()->create(['name' => 'user_admin.view']);
    Permission::query()->create(['name' => 'userXadmin.view']);

    expect(Permission::query()->byGroup('user_admin')->pluck('name')->all())
        ->toBe(['user_admin.view']);
});

it('treats percent signs and the escape character in byGroup as literal characters', function (): void {
    Permission::query()->create(['name' => 'a%b.view']);
    Permission::query()->create(['name' => 'aXXb.view']);
    Permission::query()->create(['name' => 'a!b.view']);

    expect(Permission::query()->byGroup('a%b')->pluck('name')->all())->toBe(['a%b.view'])
        ->and(Permission::query()->byGroup('a!b')->pluck('name')->all())->toBe(['a!b.view']);
});

it('recalculates is_wildcard on every save so it cannot drift from the name', function (): void {
    $permission = Permission::query()->create(['name' => 'reports.view']);

    $permission->update(['is_wildcard' => true]);

    expect($permission->fresh()->is_wildcard)->toBeFalse();
});

it('fills group from the name when none is given', function (): void {
    expect(Permission::query()->create(['name' => 'invoices.send'])->group)->toBe('invoices');
});

it('keeps an explicitly given group', function (): void {
    expect(Permission::query()->create(['name' => 'invoices.send', 'group' => 'billing'])->fresh()->group)->toBe('billing');
});

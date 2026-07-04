<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Models\Permission;
use AmdadulHaq\Custodian\Models\Role;
use AmdadulHaq\Custodian\Tests\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    $this->user = User::query()->create([
        'name' => 'Blade User',
        'email' => 'blade@example.com',
        'password' => 'password',
    ]);

    $this->role = Role::query()->create(['name' => 'admin']);
    $this->permission = Permission::query()->create(['name' => 'users.create']);

    $this->role->givePermissionTo($this->permission);
    $this->user->assignRole($this->role);
    $this->user->refresh();
});

it('authorizes permissions through Laravel Gate', function (): void {
    expect(Gate::forUser($this->user)->allows('users.create'))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('users.delete'))->toBeFalse();
});

it('authorizes roles through Laravel Gate', function (): void {
    expect(Gate::forUser($this->user)->allows('admin'))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('editor'))->toBeFalse();
});

it('renders custom blade role directives', function (): void {
    $this->user->assignRole($this->role);

    $this->actingAs($this->user);

    expect(Blade::render("@role('admin') true @endrole"))->toBe('true ')
        ->and(Blade::render("@role('editor') true @endrole"))->toBe('')
        ->and(Blade::render("@hasrole('admin') true @endhasrole"))->toBe('true ')
        ->and(Blade::render("@hasrole('editor') true @endhasrole"))->toBe('');
});

it('renders custom blade multiple-role directives', function (): void {
    $this->be($this->user);

    $html = Blade::render(
        <<<'BLADE'
        @hasanyrole(['admin', 'editor'])
        <span>any-role</span>
        @endhasanyrole
        @hasallroles(['admin', 'editor'])
        <span>all-roles</span>
        @endhasallroles
        BLADE,
        [],
        deleteCachedView: true
    );

    expect($html)->toContain('any-role')
        ->not->toContain('all-roles');
});

it('denies undefined abilities and still runs app-defined gates', function (): void {
    Gate::define('custom-app-gate', fn ($user): bool => true);

    expect(Gate::forUser($this->user)->allows('custom-app-gate'))->toBeTrue()
        ->and(Gate::forUser($this->user)->allows('totally.undefined'))->toBeFalse();
});

it('authorizes a permission created after boot without re-registration', function (): void {
    $permission = Permission::query()->create(['name' => 'reports.view']);
    $this->role->givePermissionTo($permission);

    expect(Gate::forUser($this->user->fresh())->allows('reports.view'))->toBeTrue();
});

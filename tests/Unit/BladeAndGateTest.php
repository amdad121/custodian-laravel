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

it('leaves ability checks with arguments to policies', function (): void {
    Role::query()->create(['name' => 'update']);
    $this->user->assignRole('update');
    $this->role->givePermissionTo(Permission::query()->create(['name' => 'delete']));

    $user = $this->user->fresh();

    expect(Gate::forUser($user)->allows('update', new stdClass))->toBeFalse()
        ->and(Gate::forUser($user)->allows('delete', new stdClass))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update'))->toBeTrue();
});

it('does not grant a role or permission ability to a model a policy denies', function (): void {
    Gate::define('users.create', fn ($user, $target): bool => false);

    expect(Gate::forUser($this->user)->allows('users.create', new stdClass))->toBeFalse()
        ->and(Gate::forUser($this->user)->allows('users.create'))->toBeTrue();
});

it('renders role directives as false for a user that is not Roleable', function (): void {
    $guest = new class extends Illuminate\Foundation\Auth\User {};
    $this->actingAs($guest);

    expect(Blade::render("@role('admin') yes @endrole"))->not->toContain('yes')
        ->and(Blade::render("@hasallroles('admin') yes @endhasallroles"))->not->toContain('yes');
});

it('still lets a super-admin with the * permission pass checks with arguments', function (): void {
    $this->role->givePermissionTo(Permission::query()->create(['name' => '*']));

    expect(Gate::forUser($this->user->fresh())->allows('update', new stdClass))->toBeTrue();
});

it('does not treat * as super-admin for checks with arguments when wildcards are disabled', function (): void {
    config()->set('custodian.wildcard.enabled', false);
    $this->role->givePermissionTo(Permission::query()->create(['name' => '*']));

    expect(Gate::forUser($this->user->fresh())->allows('update', new stdClass))->toBeFalse();
});

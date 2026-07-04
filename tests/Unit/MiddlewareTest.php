<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Exceptions\PermissionDeniedException;
use AmdadulHaq\Custodian\Middleware\PermissionMiddleware;
use AmdadulHaq\Custodian\Middleware\RoleMiddleware;
use AmdadulHaq\Custodian\Middleware\RoleOrPermissionMiddleware;
use AmdadulHaq\Custodian\Models\Permission;
use AmdadulHaq\Custodian\Models\Role;
use AmdadulHaq\Custodian\Tests\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->user = User::query()->create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password']);
    $this->role = Role::query()->create(['name' => 'admin']);
    $this->permission = Permission::query()->create(['name' => 'users.create']);
});

it('allows access when user has required role via RoleMiddleware', function (): void {
    $this->user->assignRole($this->role);

    Route::middleware([RoleMiddleware::class.':admin'])->get('/test-role', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-role');

    expect($response->status())->toBe(200)
        ->and($response->content())->toBe('success');
});

it('throws PermissionDeniedException when user lacks required role via RoleMiddleware', function (): void {
    Route::middleware([RoleMiddleware::class.':admin'])->get('/test-role', fn (): string => 'success');

    $this->withoutExceptionHandling()->actingAs($this->user)->get('/test-role');
})->throws(PermissionDeniedException::class);

it('returns 401 when unauthenticated user accesses role protected route via RoleMiddleware', function (): void {
    Route::middleware([RoleMiddleware::class.':admin'])->get('/test-role', fn (): string => 'success');

    $response = $this->get('/test-role');

    expect($response->status())->toBe(401);
});

it('allows access when user has any of multiple roles via RoleMiddleware', function (): void {
    $editorRole = Role::query()->create(['name' => 'editor']);
    $this->user->assignRole($editorRole);

    Route::middleware([RoleMiddleware::class.':admin,editor'])->get('/test-multi-role', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-multi-role');

    expect($response->status())->toBe(200);
});

it('allows access when user has required permission via PermissionMiddleware', function (): void {
    $this->role->givePermissionTo($this->permission);
    $this->user->assignRole($this->role);

    Route::middleware([PermissionMiddleware::class.':users.create'])->get('/test-permission', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-permission');

    expect($response->status())->toBe(200)
        ->and($response->content())->toBe('success');
});

it('throws PermissionDeniedException when user lacks required permission via PermissionMiddleware', function (): void {
    Route::middleware([PermissionMiddleware::class.':users.create'])->get('/test-permission', fn (): string => 'success');

    $this->withoutExceptionHandling()->actingAs($this->user)->get('/test-permission');
})->throws(PermissionDeniedException::class);

it('renders a 403 response when an authenticated user lacks the required role', function (): void {
    Route::middleware([RoleMiddleware::class.':admin'])->get('/test-role-403', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-role-403');

    expect($response->status())->toBe(403);
});

it('renders a 403 response when an authenticated user lacks the required permission', function (): void {
    Route::middleware([PermissionMiddleware::class.':users.create'])->get('/test-permission-403', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-permission-403');

    expect($response->status())->toBe(403);
});

it('allows access via role through RoleOrPermissionMiddleware', function (): void {
    $this->user->assignRole($this->role);

    Route::middleware([RoleOrPermissionMiddleware::class.':admin,users.create'])->get('/test-rop-role', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-rop-role');

    expect($response->status())->toBe(200);
});

it('allows access via permission through RoleOrPermissionMiddleware', function (): void {
    $this->role->givePermissionTo($this->permission);
    $this->user->assignRole($this->role);

    Route::middleware([RoleOrPermissionMiddleware::class.':editor,users.create'])->get('/test-rop-permission', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-rop-permission');

    expect($response->status())->toBe(200);
});

it('renders a 403 response when user has neither role nor permission via RoleOrPermissionMiddleware', function (): void {
    Route::middleware([RoleOrPermissionMiddleware::class.':editor,users.create'])->get('/test-rop-denied', fn (): string => 'success');

    $response = $this->actingAs($this->user)->get('/test-rop-denied');

    expect($response->status())->toBe(403);
});

it('returns 401 when unauthenticated user hits RoleOrPermissionMiddleware', function (): void {
    Route::middleware([RoleOrPermissionMiddleware::class.':admin,users.create'])->get('/test-rop-guest', fn (): string => 'success');

    $response = $this->get('/test-rop-guest');

    expect($response->status())->toBe(401);
});

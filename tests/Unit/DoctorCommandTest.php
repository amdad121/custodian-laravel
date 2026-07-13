<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('reports no problems when configuration is correct', function (): void {
    $this->artisan('custodian:doctor')
        ->expectsOutputToContain('No problems found')
        ->assertExitCode(0);
});

it('reports a problem when the role model is misconfigured', function (): void {
    config()->set('custodian.models.role', 'App\\Models\\DoesNotExist');

    $this->artisan('custodian:doctor')
        ->expectsOutputToContain('does not exist')
        ->assertExitCode(1);
});

it('reports a problem when a configured table is missing', function (): void {
    config()->set('custodian.tables.roles', 'nonexistent_roles_table');

    $this->artisan('custodian:doctor')
        ->expectsOutputToContain('does not exist')
        ->assertExitCode(1);
});

it('reports a problem when a pivot table is missing', function (): void {
    Schema::drop('permission_role');

    $this->artisan('custodian:doctor')
        ->expectsOutputToContain('Pivot table [permission_role]')
        ->assertExitCode(1);
});

it('reports a problem when the roles table is missing the is_protected column', function (): void {
    Schema::table('roles', function (Blueprint $table): void {
        $table->dropColumn('is_protected');
    });

    $this->artisan('custodian:doctor')
        ->expectsOutputToContain('is missing the [is_protected] column')
        ->assertExitCode(1);
});

it('reports a problem when a middleware alias is not configured', function (): void {
    config()->set('custodian.middleware.role', '');

    $this->artisan('custodian:doctor')
        ->expectsOutputToContain("config('custodian.middleware.role') is not set.")
        ->assertExitCode(1);
});

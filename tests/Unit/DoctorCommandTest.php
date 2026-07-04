<?php

declare(strict_types=1);

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

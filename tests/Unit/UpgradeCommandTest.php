<?php

declare(strict_types=1);

it('reports there are no upgrade steps yet', function (): void {
    $this->artisan('custodian:upgrade')
        ->expectsOutputToContain('No upgrade steps are defined yet')
        ->assertExitCode(0);
});

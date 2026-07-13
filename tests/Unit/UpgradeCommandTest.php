<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('reports when no rewrites are needed', function (): void {
    $this->artisan('custodian:upgrade')
        ->expectsOutputToContain('No upgrade rewrites were needed.')
        ->assertExitCode(0);
});

it('rewrites is_guarded and GuardedRoleException usages to their new names', function (): void {
    $file = app_path('Models/LegacyRole.php');

    File::ensureDirectoryExists(app_path('Models'));
    File::put($file, <<<'PHP'
        <?php

        use AmdadulHaq\Custodian\Exceptions\GuardedRoleException;

        $role->is_guarded = true;
        Role::query()->guarded()->get();
        Role::query()->unguarded()->get();
        PHP);

    try {
        $this->artisan('custodian:upgrade')
            ->expectsOutputToContain(sprintf('Updated %s', $file))
            ->assertExitCode(0);

        $contents = File::get($file);

        expect($contents)
            ->toContain('ProtectedRoleException')
            ->toContain('is_protected')
            ->toContain('->protected()')
            ->toContain('->unprotected()')
            ->not->toContain('is_guarded')
            ->not->toContain('GuardedRoleException');
    } finally {
        File::delete($file);
    }
});

it('rewrites usages in views and tests directories', function (): void {
    $viewFile = resource_path('views/roles/edit.blade.php');
    $testFile = base_path('tests/Feature/LegacyRoleTest.php');

    File::ensureDirectoryExists(resource_path('views/roles'));
    File::ensureDirectoryExists(base_path('tests/Feature'));

    File::put($viewFile, <<<'BLADE'
        @if($role->is_guarded)
            <span>Protected</span>
        @endif
        BLADE);

    File::put($testFile, <<<'PHP'
        <?php

        Role::query()->guarded()->get();
        PHP);

    try {
        $this->artisan('custodian:upgrade')->assertExitCode(0);

        expect(File::get($viewFile))->toContain('is_protected')->not->toContain('is_guarded');
        expect(File::get($testFile))->toContain('->protected()')->not->toContain('->guarded()');
    } finally {
        File::delete($viewFile);
        File::delete($testFile);
    }
});

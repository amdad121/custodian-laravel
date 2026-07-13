<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/** Applies automated code rewrites for upgrading between Custodian versions. */
class UpgradeCommand extends Command
{
    /**
     * Regex rewrite map applied to app/database/views/tests code when upgrading past v1.0.0.
     *
     * `is_guarded` / `GuardedRoleException` / `guarded()` / `unguarded()` were renamed
     * to `is_protected` / `ProtectedRoleException` / `protected()` / `unprotected()`
     * to avoid colliding with Eloquent's own `$guarded` mass-assignment property.
     *
     * @var array<string, string>
     */
    private const REWRITES = [
        '/\bis_guarded\b/' => 'is_protected',
        '/\bGuardedRoleException\b/' => 'ProtectedRoleException',
        '/->guarded\(\)/' => '->protected()',
        '/->unguarded\(\)/' => '->unprotected()',
        '/\bscopeGuarded\b/' => 'scopeProtected',
        '/\bscopeUnguarded\b/' => 'scopeUnprotected',
    ];

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'custodian:upgrade';

    /**
     * The console command description.
     */
    protected $description = 'Upgrade application code between Custodian versions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $paths = array_filter(
            [app_path(), database_path(), resource_path('views'), base_path('tests')],
            fn (string $path): bool => File::isDirectory($path)
        );

        if ($paths === []) {
            $this->info('No app, database, views, or tests directories found to scan.');

            return self::SUCCESS;
        }

        $changed = 0;

        foreach ($paths as $path) {
            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $original = $file->getContents();
                $rewritten = preg_replace(array_keys(self::REWRITES), array_values(self::REWRITES), $original);

                if ($rewritten !== null && $rewritten !== $original) {
                    File::put($file->getPathname(), $rewritten);
                    $this->line('Updated '.$file->getPathname());
                    $changed++;
                }
            }
        }

        if ($changed === 0) {
            $this->info('No upgrade rewrites were needed.');
        } else {
            $this->info(sprintf('Updated %d file(s). Review the changes with git diff before committing.', $changed));
        }

        return self::SUCCESS;
    }
}

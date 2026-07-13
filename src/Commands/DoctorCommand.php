<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Commands;

use AmdadulHaq\Custodian\Contracts\Permissionable;
use AmdadulHaq\Custodian\Contracts\Roleable;
use AmdadulHaq\Custodian\Facades\Custodian;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Diagnoses common Custodian configuration problems before they cause runtime failures. */
class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'custodian:doctor';

    /**
     * The console command description.
     */
    protected $description = 'Check the Custodian configuration for common problems';

    /**
     * Number of problems found.
     */
    protected int $problems = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->checkModelClasses();
        $this->checkTables();
        $this->checkPivotTables();
        $this->checkUserModel();
        $this->checkMiddlewareConfig();
        $this->checkWildcardConfig();

        $this->newLine();

        if ($this->problems === 0) {
            $this->info('No problems found. Custodian is configured correctly.');

            return self::SUCCESS;
        }

        $this->error(sprintf('%d problem(s) found.', $this->problems));

        return self::FAILURE;
    }

    /**
     * Verify the configured model classes exist, extend Eloquent Model,
     * and (for the role model) implement the Permissionable contract.
     */
    protected function checkModelClasses(): void
    {
        foreach (['role', 'permission', 'user'] as $key) {
            $class = config('custodian.models.'.$key);

            if (! is_string($class) || $class === '') {
                $this->reportFail(sprintf("config('custodian.models.%s') is not set.", $key));

                continue;
            }

            if (! class_exists($class)) {
                $this->reportFail(sprintf('Configured %s model [%s] does not exist.', $key, $class));

                continue;
            }

            if (! is_subclass_of($class, Model::class)) {
                $this->reportFail(sprintf('Configured %s model [%s] does not extend Illuminate\Database\Eloquent\Model.', $key, $class));

                continue;
            }

            if ($key === 'role' && ! in_array(Permissionable::class, class_implements($class) ?: [], true)) {
                $this->reportFail(sprintf('Configured role model [%s] does not implement AmdadulHaq\Custodian\Contracts\Permissionable.', $class));

                continue;
            }

            $this->reportPass(sprintf('Model [%s] => %s', $key, $class));
        }
    }

    /**
     * Verify the configured user model implements the Roleable contract.
     */
    protected function checkUserModel(): void
    {
        $userClass = config('custodian.models.user');

        if (! is_string($userClass) || ! class_exists($userClass)) {
            return;
        }

        if (! in_array(Roleable::class, class_implements($userClass) ?: [], true)) {
            $this->reportFail(sprintf('User model [%s] does not implement AmdadulHaq\Custodian\Contracts\Roleable. Role/permission checks will not work.', $userClass));

            return;
        }

        $this->reportPass('User model implements Roleable.');
    }

    /**
     * Verify the configured tables exist in the database.
     */
    protected function checkTables(): void
    {
        $tables = config('custodian.tables', []);

        if (! is_array($tables)) {
            $this->reportFail("config('custodian.tables') is not an array.");

            return;
        }

        foreach (['roles', 'permissions'] as $key) {
            $table = $tables[$key] ?? null;

            if (! is_string($table) || $table === '') {
                $this->reportFail(sprintf("config('custodian.tables.%s') is not set.", $key));

                continue;
            }

            try {
                if (! Schema::hasTable($table)) {
                    $this->reportFail(sprintf('Table [%s] does not exist. Run: php artisan vendor:publish --tag="custodian-migrations" && php artisan migrate', $table));

                    continue;
                }

                if ($key === 'roles' && ! Schema::hasColumn($table, 'is_protected')) {
                    $this->reportFail(sprintf('Table [%s] is missing the [is_protected] column. If you upgraded from v1.x, run: php artisan custodian:upgrade and add a migration renaming is_guarded to is_protected. See UPGRADE.md.', $table));

                    continue;
                }
            } catch (Throwable $e) {
                $this->reportFail(sprintf('Could not check table [%s]: %s', $table, $e->getMessage()));

                continue;
            }

            $this->reportPass(sprintf('Table [%s] => %s', $key, $table));
        }
    }

    /**
     * Verify the derived role<->permission and role<->user pivot tables exist.
     */
    protected function checkPivotTables(): void
    {
        $models = config('custodian.models', []);

        if (! is_array($models)) {
            return;
        }

        foreach ([
            'role<->permission' => ['permission', 'role'],
            'role<->user' => ['role', 'user'],
        ] as $label => $keys) {
            $pair = Arr::only($models, $keys);

            if (count($pair) !== count($keys)) {
                continue;
            }

            if (in_array('', $pair, true)) {
                continue;
            }

            if (array_filter($pair, fn ($v): bool => ! is_string($v) || ! class_exists($v)) !== []) {
                continue;
            }

            try {
                $table = Custodian::getPivotTableName($pair);

                if (! Schema::hasTable($table)) {
                    $this->reportFail(sprintf('Pivot table [%s] for %s does not exist. Run: php artisan vendor:publish --tag="custodian-migrations" && php artisan migrate', $table, $label));

                    continue;
                }

                $this->reportPass(sprintf('Pivot table [%s] => %s', $label, $table));
            } catch (Throwable $e) {
                $this->reportFail(sprintf('Could not determine pivot table for %s: %s', $label, $e->getMessage()));
            }
        }
    }

    /**
     * Verify the middleware aliases are configured with non-empty string values.
     */
    protected function checkMiddlewareConfig(): void
    {
        $middleware = config('custodian.middleware', []);

        if (! is_array($middleware)) {
            $this->reportFail("config('custodian.middleware') is not an array.");

            return;
        }

        foreach (['role', 'permission', 'role_or_permission'] as $key) {
            $alias = $middleware[$key] ?? null;

            if (! is_string($alias) || $alias === '') {
                $this->reportFail(sprintf("config('custodian.middleware.%s') is not set.", $key));

                continue;
            }

            $this->reportPass(sprintf('Middleware [%s] => %s', $key, $alias));
        }
    }

    /**
     * Sanity-check the wildcard config value.
     */
    protected function checkWildcardConfig(): void
    {
        $enabled = config('custodian.wildcard.enabled');

        if (! is_bool($enabled)) {
            $this->reportFail("config('custodian.wildcard.enabled') should be a boolean.");

            return;
        }

        $this->reportPass('Wildcard config => '.($enabled ? 'enabled' : 'disabled'));
    }

    /**
     * Record and print a passing check.
     */
    protected function reportPass(string $message): void
    {
        $this->line('<fg=green>PASS</> '.$message);
    }

    /**
     * Record and print a failing check.
     */
    protected function reportFail(string $message): void
    {
        $this->problems++;
        $this->line('<fg=red>FAIL</> '.$message);
    }
}

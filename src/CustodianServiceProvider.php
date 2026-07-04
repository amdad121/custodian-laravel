<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian;

use AmdadulHaq\Custodian\Commands\CreatePermission;
use AmdadulHaq\Custodian\Commands\CreateRole;
use AmdadulHaq\Custodian\Commands\DoctorCommand;
use AmdadulHaq\Custodian\Commands\UpgradeCommand;
use AmdadulHaq\Custodian\Contracts\Roleable;
use AmdadulHaq\Custodian\Middleware\PermissionMiddleware;
use AmdadulHaq\Custodian\Middleware\RoleMiddleware;
use AmdadulHaq\Custodian\Middleware\RoleOrPermissionMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

class CustodianServiceProvider extends ServiceProvider
{
    /**
     * Memoized result of the permissions-table existence check,
     * so gate checks don't hit the schema more than once per request.
     */
    protected ?bool $permissionsTableExists = null;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/custodian.php',
            'custodian'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerCommands();
        $this->registerMiddleware();
        $this->registerBladeDirectives();

        $this->registerGateHook();
    }

    /**
     * Register a Gate::before hook that resolves abilities as
     * permissions or roles. Returns null when the ability is not
     * granted so explicitly defined gates and policies still run.
     */
    protected function registerGateHook(): void
    {
        Gate::before(function (mixed $user, string $ability): ?bool {
            if (! $user instanceof Roleable) {
                return null;
            }

            if (! $this->permissionsTableExists()) {
                return null;
            }

            return ($user->hasPermission($ability) || $user->hasRole($ability)) ? true : null;
        });
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/custodian.php' => config_path('custodian.php'),
        ], 'custodian-config');
    }

    /**
     * Publish the migration files.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__.'/../database/migrations/create_roles_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_roles_table.php'),
            __DIR__.'/../database/migrations/create_permissions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_permissions_table.php'),
        ], 'custodian-migrations');
    }

    /**
     * Register the console commands for this package.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            CreateRole::class,
            CreatePermission::class,
            UpgradeCommand::class,
            DoctorCommand::class,
        ]);
    }

    /**
     * Register the package middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        $middleware = config('custodian.middleware', []);

        $router->aliasMiddleware($middleware['role'] ?? 'role', RoleMiddleware::class);
        $router->aliasMiddleware($middleware['permission'] ?? 'permission', PermissionMiddleware::class);
        $router->aliasMiddleware($middleware['role_or_permission'] ?? 'role_or_permission', RoleOrPermissionMiddleware::class);
    }

    /**
     * Register Blade directives for role and permission checking.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('role', fn (string $expression): string => sprintf('<?php if(auth()->check() && auth()->user()->hasRole(%s)): ?>', $expression));

        Blade::directive('endrole', fn (): string => '<?php endif; ?>');

        Blade::directive('hasrole', fn (string $expression): string => sprintf('<?php if(auth()->check() && auth()->user()->hasRole(%s)): ?>', $expression));

        Blade::directive('endhasrole', fn (): string => '<?php endif; ?>');

        Blade::directive('hasanyrole', fn (string $expression): string => sprintf('<?php if(auth()->check() && auth()->user()->hasAnyRole(%s)): ?>', $expression));

        Blade::directive('endhasanyrole', fn (): string => '<?php endif; ?>');

        Blade::directive('hasallroles', fn (string $expression): string => sprintf('<?php if(auth()->check() && auth()->user()->hasAllRoles(%s)): ?>', $expression));

        Blade::directive('endhasallroles', fn (): string => '<?php endif; ?>');
    }

    /**
     * Check if the permissions table exists.
     */
    protected function permissionsTableExists(): bool
    {
        // Only a positive result is memoized: a missing table can appear
        // later (mid-migration, long-lived Octane workers), but an existing
        // table never legitimately disappears within a worker's lifetime.
        if ($this->permissionsTableExists === true) {
            return true;
        }

        try {
            $table = config('custodian.tables.permissions');

            return $this->permissionsTableExists = is_string($table) && Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}

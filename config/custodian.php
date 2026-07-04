<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Models\Permission;
use AmdadulHaq\Custodian\Models\Role;

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Fully qualified class names for the models used by Custodian. You can
    | extend or replace the default Role and Permission models here.
    |
    */

    'models' => [
        'user' => 'App\\Models\\User',
        'role' => Role::class,
        'permission' => Permission::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | Database table names for the roles and permissions system.
    |
    */

    'tables' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware aliases registered by Custodian for use in route definitions.
    |
    */

    'middleware' => [
        'role' => 'role',
        'permission' => 'permission',
        'role_or_permission' => 'role_or_permission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wildcard Permissions
    |--------------------------------------------------------------------------
    |
    | When enabled, a wildcard permission such as 'user.*' will match any
    | permission with the 'user.' prefix (e.g. 'user.update', 'user.delete').
    |
    */

    'wildcard' => [
        'enabled' => env('CUSTODIAN_WILDCARD_ENABLED', true),
    ],
];

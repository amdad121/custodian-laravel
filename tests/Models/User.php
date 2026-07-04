<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Tests\Models;

use AmdadulHaq\Custodian\Concerns\Roleable;
use AmdadulHaq\Custodian\Contracts\Roleable as RoleableContract;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements RoleableContract
{
    use Roleable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
}

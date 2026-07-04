<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AmdadulHaq\Custodian\Custodian
 */
class Custodian extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AmdadulHaq\Custodian\Custodian::class;
    }
}

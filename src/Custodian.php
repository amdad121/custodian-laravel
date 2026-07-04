<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian;

use Illuminate\Support\Str;

class Custodian
{
    /**
     * Get singular name of a string.
     */
    public function getSingularName(string $string): string
    {
        return Str::singular($string);
    }

    /**
     * Get table name of a model.
     */
    public function getTableName(string $class): string
    {
        return resolve($class)->getTable();
    }

    /**
     * Get pivot table name for a given array of models.
     *
     * @param  array<array-key, string>  $array
     */
    public function getPivotTableName(array $array): string
    {
        $names = array_map(
            fn (string $value): string => $this->getSingularName($this->getTableName($value)),
            $array
        );

        sort($names);

        return implode('_', $names);
    }
}

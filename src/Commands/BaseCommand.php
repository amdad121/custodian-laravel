<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

abstract class BaseCommand extends Command implements PromptsForMissingInput
{
    /**
     * Resolve a model from the config key.
     */
    protected function resolveModel(string $configKey): Model
    {
        return resolve(config('custodian.models.'.$configKey));
    }

    /**
     * Find an entity by identifier (ID or specific columns).
     *
     * @param  array<int, string>  $searchColumns
     */
    protected function findByIdentifier(Model $model, string $identifier, array $searchColumns): ?Model
    {
        if (ctype_digit($identifier)) {
            $found = $model::query()->whereKey((int) $identifier)->first();

            if ($found instanceof Model) {
                return $found;
            }
        }

        $matches = $model::query()
            ->where(function (Builder $query) use ($identifier, $searchColumns): void {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, $identifier);
                }
            })
            ->limit(2)
            ->get();

        if ($matches->count() > 1) {
            throw new InvalidArgumentException(sprintf('More than one record matches [%s]. Use an ID instead.', $identifier));
        }

        return $matches->first();
    }
}

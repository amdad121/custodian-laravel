<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        if (is_numeric($identifier)) {
            return $model::query()->whereKey((int) $identifier)->first();
        }

        return $model::query()
            ->where(function (Builder $query) use ($identifier, $searchColumns): void {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, $identifier);
                }
            })
            ->first();
    }
}

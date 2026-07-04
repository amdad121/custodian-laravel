<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;

trait HasCustodianHelpers
{
    /**
     * Resolve a model from an identifier (name or ID) or return the provided model.
     *
     * @return ($throw is true ? Model : Model|null)
     */
    protected function resolveModel(string $configKey, Model|string|int $identifier, bool $throw = true): ?Model
    {
        if ($identifier instanceof Model) {
            return $identifier;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = config('custodian.models.'.$configKey);
        $query = $modelClass::query();

        if (is_numeric($identifier)) {
            $query->whereKey((int) $identifier);
        } else {
            $query->where('name', $identifier);
        }

        return $throw ? $query->firstOrFail() : $query->first();
    }

    /**
     * Get IDs from array of models, IDs, or names.
     *
     * @param  array<int, Model|int|string>  $items
     * @return array<int, int>
     *
     * @throws ModelNotFoundException When a name does not resolve to a model.
     */
    protected function getModelIds(string $configKey, array $items): array
    {
        return collect($items)
            ->map(function (Model|int|string $item) use ($configKey): int {
                if ($item instanceof Model) {
                    return (int) $item->getKey();
                }

                if (is_numeric($item)) {
                    return (int) $item;
                }

                return (int) $this->resolveModel($configKey, $item)->getKey();
            })
            ->values()
            ->all();
    }

    /**
     * Flatten arguments into a simple array.
     *
     * @param  array<array-key, mixed>  $args
     * @return array<int, Model|int|string>
     */
    protected function flattenArgs(array $args): array
    {
        /** @var array<int, Model|int|string> */
        return Arr::flatten($args);
    }
}

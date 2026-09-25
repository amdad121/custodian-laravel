<?php

declare(strict_types=1);

namespace AmdadulHaq\Custodian\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\UniqueConstraintViolationException;
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

        // Digit-only strings are tried as a key first, then as a name, so
        // roles/permissions with numeric names (e.g. "2024") still resolve.
        if (is_int($identifier) || ctype_digit($identifier)) {
            $model = $query->clone()->whereKey((int) $identifier)->first();

            if ($model instanceof Model || is_int($identifier)) {
                if (! $model instanceof Model && $throw) {
                    throw (new ModelNotFoundException)->setModel($modelClass, [$identifier]);
                }

                return $model;
            }
        }

        $query->where('name', $identifier);

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

                if (is_int($item)) {
                    return $item;
                }

                return (int) $this->resolveModel($configKey, $item)->getKey();
            })
            ->values()
            ->all();
    }

    /**
     * Attach without detaching, retrying once if a concurrent request
     * inserted the same pivot row between the read and the insert.
     *
     * @template TDeclaringModel of Model
     *
     * @param  BelongsToMany<Model, TDeclaringModel>  $relation
     * @param  array<int, int>  $ids
     * @return array<string, mixed>
     */
    protected function attachMissing(BelongsToMany $relation, array $ids): array
    {
        try {
            return $relation->syncWithoutDetaching($ids);
        } catch (UniqueConstraintViolationException) {
            return $relation->syncWithoutDetaching($ids);
        }
    }

    /**
     * Cast pivot IDs returned by sync() to integers.
     *
     * @param  array<array-key, mixed>  $ids
     * @return array<int, int>
     */
    protected function castIds(array $ids): array
    {
        return array_values(array_map(intval(...), $ids));
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

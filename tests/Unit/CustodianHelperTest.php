<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Custodian;
use AmdadulHaq\Custodian\Tests\Models\CustomRole;
use AmdadulHaq\Custodian\Tests\Models\User;

beforeEach(function (): void {
    $this->custodian = new Custodian;
});

it('singularizes plural strings', function (): void {
    expect($this->custodian->getSingularName('roles'))->toBe('role')
        ->and($this->custodian->getSingularName('permissions'))->toBe('permission');
});

it('leaves an already-singular string untouched', function (): void {
    expect($this->custodian->getSingularName('role'))->toBe('role');
});

it('resolves a model class to its table name', function (): void {
    expect($this->custodian->getTableName(User::class))->toBe((new User)->getTable())
        ->and($this->custodian->getTableName(CustomRole::class))->toBe((new CustomRole)->getTable());
});

it('builds a pivot table name from two models, sorted alphabetically', function (): void {
    $name = $this->custodian->getPivotTableName([User::class, CustomRole::class]);

    $userSingular = $this->custodian->getSingularName((new User)->getTable());
    $roleSingular = $this->custodian->getSingularName((new CustomRole)->getTable());
    $expected = implode('_', collect([$userSingular, $roleSingular])->sort()->values()->all());

    expect($name)->toBe($expected);
});

it('produces the same pivot table name regardless of argument order', function (): void {
    $first = $this->custodian->getPivotTableName([User::class, CustomRole::class]);
    $second = $this->custodian->getPivotTableName([CustomRole::class, User::class]);

    expect($first)->toBe($second);
});

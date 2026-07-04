<?php

declare(strict_types=1);

use AmdadulHaq\Custodian\Enums\PermissionType;

it('has permission type enum values', function (): void {
    expect(PermissionType::READ->value)->toBe('read');
    expect(PermissionType::CREATE->value)->toBe('create');
    expect(PermissionType::UPDATE->value)->toBe('update');
    expect(PermissionType::DELETE->value)->toBe('delete');
});

it('permission type has label method', function (): void {
    expect(PermissionType::VIEW_ANY->label())->toBe('View any');
    expect(PermissionType::FORCE_DELETE->label())->toBe('Force delete');
});

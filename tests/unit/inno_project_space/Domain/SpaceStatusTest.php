<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

test('SpaceStatus has four cases with correct string values', function () {
    expect(SpaceStatus::Draft->value)->toBe('draft')
        ->and(SpaceStatus::Active->value)->toBe('active')
        ->and(SpaceStatus::Completed->value)->toBe('completed')
        ->and(SpaceStatus::Archived->value)->toBe('archived');
});

test('SpaceStatus can be created from string value', function () {
    expect(SpaceStatus::from('active'))->toBe(SpaceStatus::Active);
});

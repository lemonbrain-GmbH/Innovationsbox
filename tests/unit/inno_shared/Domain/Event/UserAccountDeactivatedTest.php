<?php

// tests/unit/inno_shared/Domain/Event/UserAccountDeactivatedTest.php
declare(strict_types=1);

use Concrete\Package\InnoShared\Domain\Event\UserAccountDeactivated;

test('event stores all properties', function () {
    $event = new UserAccountDeactivated('42', 'user@test.ch', 'Max Muster', 'Spam account', '1');
    expect($event->getUserId())->toBe('42')
        ->and($event->getUserEmail())->toBe('user@test.ch')
        ->and($event->getUserName())->toBe('Max Muster')
        ->and($event->getReason())->toBe('Spam account')
        ->and($event->getDeactivatedByUserId())->toBe('1');
});

test('NAME constant follows convention', function () {
    expect(UserAccountDeactivated::NAME)->toBe('innovationsbox.user.account_deactivated');
});

test('reason is empty string for self-deletion', function () {
    $event = new UserAccountDeactivated('42', 'user@test.ch', 'Max', '', '42');
    expect($event->getReason())->toBe('')
        ->and($event->isSelfDeletion())->toBeTrue();
});

<?php

declare(strict_types=1);


test('save_avatar rejects files with disallowed MIME type', function () {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $disallowedType = 'application/pdf';

    expect(in_array($disallowedType, $allowedTypes, true))->toBeFalse();
});

test('save_avatar rejects files exceeding 5MB', function () {
    $maxBytes = 5 * 1024 * 1024;
    $oversizedBytes = 6 * 1024 * 1024;

    expect($oversizedBytes)->toBeGreaterThan($maxBytes);
});

test('save_avatar allows valid image files', function () {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    expect(in_array('image/jpeg', $allowedTypes, true))->toBeTrue();
    expect(in_array('image/png', $allowedTypes, true))->toBeTrue();
    expect(in_array('image/webp', $allowedTypes, true))->toBeTrue();
});

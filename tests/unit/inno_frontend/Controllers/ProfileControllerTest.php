<?php

declare(strict_types=1);

$profileControllerPath = __DIR__ . '/../../../../public/packages/inno_frontend/controllers/single_page/members/profile.php';

test('profile controller file exists', function () use ($profileControllerPath) {
    expect(file_exists($profileControllerPath))->toBeTrue();
});

test('profile controller checks authentication before showing profile', function () use ($profileControllerPath) {
    $source = file_get_contents($profileControllerPath);

    // Must check isRegistered() in the view method
    expect($source)->toContain('isRegistered()');

    // Must redirect to login when not authenticated
    expect($source)->toContain("replace('/login')");
});

test('profile controller extends CoreProfile', function () use ($profileControllerPath) {
    $source = file_get_contents($profileControllerPath);

    expect($source)->toContain('extends CoreProfile');
    expect($source)->toContain('use Concrete\Controller\SinglePage\Members\Profile as CoreProfile');
});

test('profile controller auth check runs before parent view call', function () use ($profileControllerPath) {
    $source = file_get_contents($profileControllerPath);

    // isRegistered check must appear before parent::view
    $authPos = strpos($source, 'isRegistered()');
    $parentPos = strpos($source, 'parent::view');

    expect($authPos)->toBeLessThan($parentPos);
});

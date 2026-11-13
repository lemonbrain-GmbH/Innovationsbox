<?php

declare(strict_types=1);

use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;

test('wrap produces valid HTML with brand header', function () {
    $html = EmailTemplate::wrap('Test heading', '<p>Hello world</p>');
    expect($html)->toContain('<!DOCTYPE html');
    expect($html)->toContain('#B7CD57');
    expect($html)->toContain('Innovationsbox');
    expect($html)->toContain('Test heading');
    expect($html)->toContain('<p>Hello world</p>');
    expect($html)->toContain('automatisch');
});

test('wrap escapes heading', function () {
    $html = EmailTemplate::wrap('Test <script>alert(1)</script>', '<p>safe</p>');
    expect($html)->not->toContain('<script>');
    expect($html)->toContain('&lt;script&gt;');
});

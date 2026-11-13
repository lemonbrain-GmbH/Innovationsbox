<?php

declare(strict_types=1);

use Concrete\Package\InnoShared\Infrastructure\Http\SecurityHeadersMiddleware;

test('applyHeaders sets security headers on response', function () {
    $response = new \Symfony\Component\HttpFoundation\Response();
    $middleware = new SecurityHeadersMiddleware();
    $middleware->applyHeaders($response);
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

test('applyHeaders adds HSTS when secure', function () {
    $response = new \Symfony\Component\HttpFoundation\Response();
    (new SecurityHeadersMiddleware())->applyHeaders($response, true);
    expect($response->headers->get('Strict-Transport-Security'))->toContain('max-age=31536000');
});

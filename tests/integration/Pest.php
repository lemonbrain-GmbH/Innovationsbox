<?php

declare(strict_types=1);

/**
 * Pest configuration for the Integration test suite.
 *
 * All tests under tests/integration/ inherit the setup defined here.
 * The CMS application is already booted by tests/integration/bootstrap.php
 * (referenced from phpunit.integration.xml).
 *
 * Transaction-rollback isolation pattern
 * ──────────────────────────────────────
 * Each integration test that touches the database should wrap mutations in a
 * transaction that is rolled back in afterEach():
 *
 *   beforeEach(function () {
 *       $this->db = app(\Concrete\Core\Database\Connection\Connection::class);
 *       $this->db->beginTransaction();
 *   });
 *
 *   afterEach(function () {
 *       $this->db->rollBack();
 *   });
 *
 * This keeps the database clean between tests without truncating tables.
 */

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeUlid', function () {
    return $this->toMatch('/^[0-9A-Z]{26}$/');
});

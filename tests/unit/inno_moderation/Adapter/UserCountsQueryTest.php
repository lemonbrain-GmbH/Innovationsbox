<?php

declare(strict_types=1);

use Concrete\Package\InnoModeration\Adapter\Out\Persistence\UserCountsQuery;

afterEach(fn() => Mockery::close());

test('getCounts() returns zeros for non-moderator non-expert with no spaces', function () {
    $db = Mockery::mock(\Concrete\Core\Database\Connection\Connection::class);

    // Join requests query (always runs)
    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_project_space_members')), [99])
        ->andReturn('0');

    $query = new UserCountsQuery($db);
    $result = $query->getCounts(99, false, false);

    expect($result)->toBe([
        'moderationReviews' => 0,
        'expertRequests' => 0,
        'joinRequests' => 0,
        'total' => 0,
    ]);
});

test('getCounts() queries moderation reviews for moderator', function () {
    $db = Mockery::mock(\Concrete\Core\Database\Connection\Connection::class);

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_moderation_reviews') && str_contains($sql, 'ib_project_ideas')))
        ->andReturn('5');

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_project_space_members')), [1])
        ->andReturn('2');

    $query = new UserCountsQuery($db);
    $result = $query->getCounts(1, true, false);

    expect($result['moderationReviews'])->toBe(5)
        ->and($result['joinRequests'])->toBe(2)
        ->and($result['total'])->toBe(7);
});

test('getCounts() queries expert requests for expert', function () {
    $db = Mockery::mock(\Concrete\Core\Database\Connection\Connection::class);

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_expert_requests')), ['42'])
        ->andReturn('3');

    $db->shouldReceive('fetchOne')
        ->once()
        ->with(Mockery::on(fn($sql) => str_contains($sql, 'ib_project_space_members')), [42])
        ->andReturn('0');

    $query = new UserCountsQuery($db);
    $result = $query->getCounts(42, false, true);

    expect($result['expertRequests'])->toBe(3)
        ->and($result['total'])->toBe(3);
});

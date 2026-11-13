<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;

test('create() builds a pending expert request', function () {
    $req = ExpertRequest::create('req_1', 'expert_5', 'initiator_1', 'Need solar expertise');
    expect($req->getId())->toBe('req_1')
        ->and($req->getExpertUserId())->toBe('expert_5')
        ->and($req->getRequestedByUserId())->toBe('initiator_1')
        ->and($req->getMessage())->toBe('Need solar expertise')
        ->and($req->getStatus())->toBe(ExpertRequestStatus::Pending)
        ->and($req->getRequestedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($req->getDecidedAt())->toBeNull();
});

test('create() with null message', function () {
    $req = ExpertRequest::create('req_2', 'expert_5', 'initiator_1', null);
    expect($req->getMessage())->toBeNull();
});

test('accept() changes status to Accepted and sets decidedAt', function () {
    $req = ExpertRequest::create('req_1', 'expert_5', 'initiator_1', null);
    $req->accept();
    expect($req->getStatus())->toBe(ExpertRequestStatus::Accepted)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('decline() changes status to Declined and sets decidedAt', function () {
    $req = ExpertRequest::create('req_1', 'expert_5', 'initiator_1', null);
    $req->decline();
    expect($req->getStatus())->toBe(ExpertRequestStatus::Declined)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('fromPersistence() reconstructs all fields', function () {
    $requested = new DateTimeImmutable('2026-01-01');
    $decided = new DateTimeImmutable('2026-01-02');
    $req = ExpertRequest::fromPersistence('req_1', 'expert_5', 'initiator_1', 'Help needed', ExpertRequestStatus::Accepted, $requested, $decided);
    expect($req->getId())->toBe('req_1')
        ->and($req->getStatus())->toBe(ExpertRequestStatus::Accepted)
        ->and($req->getRequestedAt())->toBe($requested)
        ->and($req->getDecidedAt())->toBe($decided);
});

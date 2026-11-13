<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Model\MembershipRequest;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;

test('create() builds a pending MembershipRequest', function () {
    $req = MembershipRequest::create('req_1', 'user_42');
    expect($req->getId())->toBe('req_1')
        ->and($req->getUserId())->toBe('user_42')
        ->and($req->getStatus())->toBe(RequestStatus::Pending)
        ->and($req->getRequestedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($req->getDecidedAt())->toBeNull();
});

test('accept() changes status to Accepted and sets decidedAt', function () {
    $req = MembershipRequest::create('req_1', 'user_42');
    $req->accept();
    expect($req->getStatus())->toBe(RequestStatus::Accepted)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

test('reject() changes status to Rejected and sets decidedAt', function () {
    $req = MembershipRequest::create('req_1', 'user_42');
    $req->reject();
    expect($req->getStatus())->toBe(RequestStatus::Rejected)
        ->and($req->getDecidedAt())->toBeInstanceOf(DateTimeImmutable::class);
});

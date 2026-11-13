<?php

declare(strict_types=1);

use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidCollaboardUrlException;
use Concrete\Package\InnoProjectSpace\Domain\Exception\InvalidStatusTransitionException;
use Concrete\Package\InnoProjectSpace\Domain\Model\ProjectSpace;
use Concrete\Package\InnoProjectSpace\Domain\Model\RequestStatus;
use Concrete\Package\InnoProjectSpace\Domain\Model\SpaceStatus;

// --- Creation ---

test('createFromApprovedIdea() creates space in Active status with initiator as member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Solar Project', 'A great idea', 'user_1', categoryId: 'cat_bauen');
    expect($space->getId())->toBe('space_1')
        ->and($space->getProjectIdeaId())->toBe('idea_1')
        ->and($space->getTitle())->toBe('Solar Project')
        ->and($space->getDescription())->toBe('A great idea')
        ->and($space->getStatus())->toBe(SpaceStatus::Active)
        ->and($space->getInitiatorId())->toBe('user_1')
        ->and($space->getMemberIds())->toBe(['user_1']);
});

// --- Collaboard links ---

test('addCollaboardLink() adds a link and returns it', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $link = $space->addCollaboardLink('link_1', 'Board', 'https://web.collaboard.app/share/abc');
    expect($link->getId())->toBe('link_1')
        ->and($space->getCollaboardLinks())->toHaveCount(1);
});

test('addCollaboardLink() rejects invalid URL', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->addCollaboardLink('link_1', 'Bad', 'https://miro.com/board/abc');
})->throws(InvalidCollaboardUrlException::class);

test('removeCollaboardLink() removes an existing link', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->addCollaboardLink('link_1', 'Board', 'https://web.collaboard.app/share/abc');
    $space->removeCollaboardLink('link_1');
    expect($space->getCollaboardLinks())->toHaveCount(0);
});

// --- Status updates ---

test('addStatusUpdate() adds an update and returns it', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $update = $space->addStatusUpdate('upd_1', 'Kickoff', '<p>Done!</p>', 'user_1');
    expect($update->getTitle())->toBe('Kickoff')
        ->and($space->getStatusUpdates())->toHaveCount(1);
});

// --- Membership requests ---

test('requestToJoin() creates a pending request', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $req = $space->requestToJoin('req_1', 'user_2');
    expect($req->getStatus())->toBe(RequestStatus::Pending)
        ->and($space->getMembershipRequests())->toHaveCount(1);
});

test('requestToJoin() throws if user is already a member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->requestToJoin('req_1', 'user_1');
})->throws(\DomainException::class, 'already a member');

test('requestToJoin() throws if space is in Draft status', function () {
    $now = new \DateTimeImmutable();
    $space = ProjectSpace::fromPersistence(
        'space_1',
        'idea_1',
        'Title',
        'Desc',
        SpaceStatus::Draft,
        'user_1',
        $now,
        $now,
        ['user_1'],
        [],
        [],
        [],
        'cat_bauen',
    );
    $space->requestToJoin('req_1', 'user_2');
})->throws(\DomainException::class, 'not accepting');

test('acceptJoinRequest() moves request to Accepted and adds member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->requestToJoin('req_1', 'user_2');
    $space->acceptJoinRequest('req_1');
    expect($space->getMemberIds())->toContain('user_2')
        ->and($space->getMembershipRequests()[0]->getStatus())->toBe(RequestStatus::Accepted);
});

test('rejectJoinRequest() moves request to Rejected', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->requestToJoin('req_1', 'user_2');
    $space->rejectJoinRequest('req_1');
    expect($space->getMembershipRequests()[0]->getStatus())->toBe(RequestStatus::Rejected)
        ->and($space->getMemberIds())->not->toContain('user_2');
});

// --- Status transitions ---

test('activate() transitions from Draft to Active', function () {
    $now = new \DateTimeImmutable();
    $space = ProjectSpace::fromPersistence(
        'space_1',
        'idea_1',
        'Title',
        'Desc',
        SpaceStatus::Draft,
        'user_1',
        $now,
        $now,
        ['user_1'],
        [],
        [],
        [],
        'cat_bauen',
    );
    $space->activate();
    expect($space->getStatus())->toBe(SpaceStatus::Active);
});

test('complete() transitions from Active to Completed', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->complete();
    expect($space->getStatus())->toBe(SpaceStatus::Completed);
});

test('reopen() transitions from Completed to Active', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->complete();
    $space->reopen();
    expect($space->getStatus())->toBe(SpaceStatus::Active);
});

test('reopen() throws if not in Completed status', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->reopen();
})->throws(InvalidStatusTransitionException::class);

test('dearchive() transitions from Archived to Completed', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->complete();
    $space->archive();
    $space->dearchive();
    expect($space->getStatus())->toBe(SpaceStatus::Completed);
});

test('dearchive() throws if not in Archived status', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->complete();
    $space->dearchive();
})->throws(InvalidStatusTransitionException::class);

test('archive() transitions from Completed to Archived', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->complete();
    $space->archive();
    expect($space->getStatus())->toBe(SpaceStatus::Archived);
});

test('activate() throws if not in Draft status', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->activate();
})->throws(InvalidStatusTransitionException::class);

test('complete() throws if not in Active status', function () {
    $now = new \DateTimeImmutable();
    $space = ProjectSpace::fromPersistence(
        'space_1',
        'idea_1',
        'Title',
        'Desc',
        SpaceStatus::Draft,
        'user_1',
        $now,
        $now,
        ['user_1'],
        [],
        [],
        [],
        'cat_bauen',
    );
    $space->complete();
})->throws(InvalidStatusTransitionException::class);

// --- Direct member add (for invites) ---

test('addMember() adds a new member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->addMember('user_2');
    expect($space->getMemberIds())->toContain('user_2');
});

test('addMember() is idempotent', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->addMember('user_1');
    expect($space->getMemberIds())->toHaveCount(1);
});

test('removeMember removes a member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->addMember('user_2');
    $space->removeMember('user_2');
    expect($space->getMemberIds())->not->toContain('user_2')
        ->and($space->getMemberIds())->toContain('user_1');
});

test('removeMember throws when removing initiator', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->removeMember('user_1');
})->throws(\DomainException::class);

test('removeMember throws when user not a member', function () {
    $space = ProjectSpace::createFromApprovedIdea('space_1', 'idea_1', 'Title', 'Desc', 'user_1', categoryId: 'cat_bauen');
    $space->removeMember('user_99');
})->throws(\DomainException::class);

// --- Category ---

test('createFromApprovedIdea() stores categoryId', function () {
    $space = ProjectSpace::createFromApprovedIdea(
        id: 'space_1',
        projectIdeaId: 'idea_1',
        title: 'Test',
        description: 'Desc',
        initiatorId: 'user_1',
        categoryId: 'cat_mobilitaet',
    );

    expect($space->getCategoryId())->toBe('cat_mobilitaet');
});

// --- Expert requests ---

use Concrete\Package\InnoProjectSpace\Domain\Model\ExpertRequestStatus;

test('requestExpert() creates a pending expert request', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $req = $space->requestExpert('req_1', 'expert_5', 'user_1', 'Need help');
    expect($req->getExpertUserId())->toBe('expert_5')
        ->and($req->getStatus())->toBe(ExpertRequestStatus::Pending)
        ->and($space->getExpertRequests())->toHaveCount(1);
});

test('requestExpert() throws if expert is already a member', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->addMember('expert_5');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
})->throws(\DomainException::class);

test('requestExpert() throws if expert already has a pending request', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
    $space->requestExpert('req_2', 'expert_5', 'user_1', null);
})->throws(\DomainException::class);

test('acceptExpertRequest() adds expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
    $req = $space->acceptExpertRequest('req_1');
    expect($req->getStatus())->toBe(ExpertRequestStatus::Accepted)
        ->and($space->getMemberIds())->toContain('expert_5');
});

test('declineExpertRequest() does not add expert to members', function () {
    $space = ProjectSpace::createFromApprovedIdea('s_1', 'idea_1', 'Title', 'Desc', 'user_1', 'cat_1');
    $space->requestExpert('req_1', 'expert_5', 'user_1', null);
    $req = $space->declineExpertRequest('req_1');
    expect($req->getStatus())->toBe(ExpertRequestStatus::Declined)
        ->and($space->getMemberIds())->not->toContain('expert_5');
});

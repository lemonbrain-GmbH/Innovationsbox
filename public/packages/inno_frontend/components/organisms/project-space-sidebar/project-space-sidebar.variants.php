<?php

declare(strict_types=1);

return [
    'defaults' => [
        'initiatorName' => 'Maria Keller',
        'initiatorInitials' => 'MK',
        'members' => [
            ['name' => 'Maria Keller', 'initials' => 'MK', 'role' => 'initiator'],
            ['name' => 'Thomas Weber', 'initials' => 'TW', 'role' => 'developer'],
            ['name' => 'Anna Müller', 'initials' => 'AM', 'role' => 'developer'],
        ],
        'requests' => [
            ['id' => 'req_1', 'userName' => 'Lisa Schneider', 'userInitials' => 'LS'],
        ],
        'canManage' => 'true',
        'createdAt' => '15.03.2026',
        'updateCount' => '3',
    ],
];

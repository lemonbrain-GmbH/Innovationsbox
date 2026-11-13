<?php

declare(strict_types=1);

return [
    'default-connection' => 'concrete',
    'connections' => [
        'concrete' => [
            'driver' => 'c5_pdo_mysql',
            'server' => $_ENV['DB_HOSTNAME'] ?? 'db',
            'database' => $_ENV['DB_DATABASE'] ?? 'innovationsbox_test',
            'username' => $_ENV['DB_USERNAME'] ?? 'innovationsbox_test',
            'password' => $_ENV['DB_PASSWORD'] ?? 'test_password_e2e',
            'character_set' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        ],
    ],
];

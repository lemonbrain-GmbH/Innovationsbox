<?php

declare(strict_types=1);

/**
 * Sets all required profile attributes for E2E test users.
 * Without these, the ProfileCompletionChecker redirects every page view
 * to /account/edit_profile?complete=1, breaking browser and API E2E tests.
 *
 * Required attributes: display_name, nachname, faehigkeiten, expertise_area, birthdate.
 */

$users = [
    'user@e2e.test' => 'User',
    'moderator@e2e.test' => 'Moderator',
    'expert@e2e.test' => 'Expert',
    'expert2@e2e.test' => 'Expert2',
    'admin@e2e.test' => 'Admin',
    'initiator@e2e.test' => 'Initiator',
];

foreach ($users as $email => $role) {
    $ui = \Concrete\Core\User\UserInfo::getByEmail($email);
    if (!$ui) {
        echo "User not found: $email\n";
        continue;
    }

    $ui->setAttribute('display_name', "E2E $role");
    $ui->setAttribute('nachname', "E2E-$role");
    $ui->setAttribute('faehigkeiten', 'E2E Testing');
    $ui->setAttribute('expertise_area', 'E2E Testing');

    // birthdate is a date/time attribute — use a DateTime object
    $ui->setAttribute('birthdate', new \DateTime('1990-01-01'));

    echo "Set profile attributes for $email (E2E $role)\n";
}

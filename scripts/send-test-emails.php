<?php

declare(strict_types=1);

/**
 * Send all 12 notification email templates to a configured address for manual design review.
 *
 * Usage:
 *   ./vendor/bin/concrete c5:exec scripts/send-test-emails.php
 *
 * Override recipient:
 *   MAIL_TEST_TO=other@example.com ./vendor/bin/concrete c5:exec scripts/send-test-emails.php
 */

$recipient = getenv('MAIL_TEST_TO') ?: 'marco@lemonbrain.ch';

echo "Sending all 12 notification email templates to: {$recipient}\n\n";

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$mailer = $app->make(\Concrete\Core\Mail\Service::class);

// Use the same EmailTemplate as the notification handlers
use Concrete\Package\InnoNotification\Infrastructure\Email\EmailTemplate;

$emails = [
    [
        'subject' => 'Neue Projektidee eingereicht: Solarenergie für Schulen',
        'heading' => 'Neue Projektidee eingereicht',
        'body' => '<p>Eine neue Projektidee wurde eingereicht und wartet auf Moderation.</p>
            <p><strong>Titel:</strong> Solarenergie für Schulen</p>
            <p><strong>Eingereicht von:</strong> Marco Meier</p>',
    ],
    [
        'subject' => 'Deine Projektidee wurde freigegeben!',
        'heading' => 'Deine Projektidee wurde freigegeben!',
        'body' => '<p>Gratulation! Deine Projektidee <strong>«Solarenergie für Schulen»</strong> wurde freigegeben.</p>
            <p>Ein Innovation wurde für dich erstellt. Du kannst ihn jetzt einrichten und Mitglieder einladen.</p>',
    ],
    [
        'subject' => 'Projektidee konnte nicht freigegeben werden',
        'heading' => 'Projektidee konnte nicht freigegeben werden',
        'body' => '<p>Deine Projektidee konnte leider nicht freigegeben werden.</p>
            <p><strong>Begründung:</strong> Die Idee ist zu unspezifisch. Bitte konkretisiere den Nutzen für die Region Glarus.</p>',
    ],
    [
        'subject' => 'Rückmeldung zu deiner Projektidee',
        'heading' => 'Rückmeldung zu deiner Projektidee',
        'body' => '<p>Das Moderationsteam hat Rückmeldungen zu deiner Projektidee <strong>«Solarenergie für Schulen»</strong>.</p>
            <p>Bitte überarbeite sie und reiche sie erneut ein.</p>
            <p><strong>Hinweise:</strong> Bitte ergänze eine Kostenschätzung und einen Zeitplan.</p>
            <p><strong>Frist:</strong> 15.07.2026</p>',
    ],
    [
        'subject' => 'Neue Beitrittsanfrage für «Solarenergie für Schulen»',
        'heading' => 'Neue Beitrittsanfrage',
        'body' => '<p>Anna Müller möchte deinem Innovation <strong>«Solarenergie für Schulen»</strong> beitreten.</p>
            <p>Bitte bearbeite die Anfrage im Innovation.</p>',
    ],
    [
        'subject' => 'Deine Beitrittsanfrage wurde angenommen',
        'heading' => 'Deine Beitrittsanfrage wurde angenommen',
        'body' => '<p>Du wurdest als Mitglied im Innovation <strong>«Solarenergie für Schulen»</strong> aufgenommen.</p>
            <p>Du kannst jetzt aktiv mitarbeiten.</p>',
    ],
    [
        'subject' => 'Deine Beitrittsanfrage wurde abgelehnt',
        'heading' => 'Deine Beitrittsanfrage wurde abgelehnt',
        'body' => '<p>Deine Beitrittsanfrage für den Innovation <strong>«Solarenergie für Schulen»</strong> wurde leider abgelehnt.</p>',
    ],
    [
        'subject' => 'Du wurdest in einen Innovation eingeladen',
        'heading' => 'Du wurdest in einen Innovation eingeladen',
        'body' => '<p>Marco Meier hat dich in den Innovation <strong>«Solarenergie für Schulen»</strong> eingeladen.</p>
            <p>Schau dir den Innovation an und beginne mitzuarbeiten.</p>',
    ],
    [
        'subject' => 'Deine Expertise wird angefragt',
        'heading' => 'Deine Expertise wird angefragt',
        'body' => '<p>Marco Meier bittet um deine Unterstützung im Innovation <strong>«Solarenergie für Schulen»</strong>.</p>
            <p><strong>Nachricht:</strong> Wir suchen jemanden mit Erfahrung in Photovoltaik-Anlagen auf öffentlichen Gebäuden. Kannst du uns beraten?</p>
            <p>Bitte nimm die Anfrage an oder lehne sie ab.</p>',
    ],
    [
        'subject' => 'Expert·in hat die Anfrage angenommen',
        'heading' => 'Expert·in hat die Anfrage angenommen',
        'body' => '<p>Dr. Sarah Fischer hat die Expertanfrage für den Innovation <strong>«Solarenergie für Schulen»</strong> angenommen und ist jetzt Mitglied.</p>',
    ],
    [
        'subject' => 'Expert·in hat die Anfrage abgelehnt',
        'heading' => 'Expert·in hat die Anfrage abgelehnt',
        'body' => '<p>Dr. Sarah Fischer hat die Expertanfrage für den Innovation <strong>«Solarenergie für Schulen»</strong> leider abgelehnt.</p>',
    ],
    [
        'subject' => 'Neues Update in «Solarenergie für Schulen»',
        'heading' => 'Neues Update in «Solarenergie für Schulen»',
        'body' => '<p>Marco Meier hat ein neues Update im Innovation <strong>«Solarenergie für Schulen»</strong> veröffentlicht.</p>
            <p>Schau dir das Update an.</p>',
    ],
];

$sent = 0;
foreach ($emails as $i => $email) {
    $num = $i + 1;
    $mailer->reset();
    $mailer->to($recipient);
    $mailer->setSubject("[{$num}/12] {$email['subject']}");
    $mailer->setBody(EmailTemplate::wrap($email['heading'], $email['body']));
    $mailer->setBodyHTML(EmailTemplate::wrap($email['heading'], $email['body']));

    try {
        $mailer->sendMail();
        echo "  ✓ [{$num}/12] {$email['subject']}\n";
        $sent++;
    } catch (\Throwable $e) {
        echo "  ✗ [{$num}/12] {$email['subject']} — ERROR: {$e->getMessage()}\n";
    }
}

echo "\nDone. {$sent}/12 emails sent to {$recipient}.\n";
echo "Check Dashboard > Reports > Logs (filter by 'Emails') to verify.\n";

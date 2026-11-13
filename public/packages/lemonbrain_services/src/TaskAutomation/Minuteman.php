<?php

declare(strict_types=1);

namespace TaskAutomation;

use Log;
use Symfony\Component\HttpFoundation\JsonResponse;

defined('C5_EXECUTE') or die('Access Denied.');

class Minuteman
{
    // call this all minute to handle the scheduled task
    public function runScheduler()
    {
        shell_exec('concrete/bin/concrete concrete:scheduler:run >> /dev/null 2>&1');

        // Log::addInfo('Minuteman has run...');
        return new JsonResponse(null, 200);
    }

    // Call this all minute per cronjob, it will then work through the queue and kill them self
    public function runMessengerConsumer()
    {
        shell_exec('concrete/bin/concrete messenger:consume async --memory-limit=128M --time-limit=60 >> /dev/null 2>&1');

        // Log::addInfo('Minuteman has run...');
        return new JsonResponse(null, 200);
    }
}

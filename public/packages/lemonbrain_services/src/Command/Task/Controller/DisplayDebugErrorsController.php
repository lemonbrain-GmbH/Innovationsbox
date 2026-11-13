<?php

declare(strict_types=1);

namespace LemonbrainServices\Command\Task\Controller;

use Concrete\Core\Command\Batch\Batch;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\BatchProcessTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use LemonbrainServices\Command\DisplayDebugErrorsCommand;

defined('C5_EXECUTE') or die('Access Denied.');

class DisplayDebugErrorsController extends AbstractController
{
    public const TASK_HANDLE = 'display_debug_errors';

    public function getName(): string
    {
        return t('Disable Display Debug Errors');
    }

    public function getDescription(): string
    {
        return t('In Debug Settings: Turns "Output error information to site users" off.');
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $batch = Batch::create(t('Turn of Display Debug Errors'));
        $batch->add(new DisplayDebugErrorsCommand());
        return new BatchProcessTaskRunner($task, $batch, $input, t('Starting to Turn off Display Debug Errors...'));
    }
}

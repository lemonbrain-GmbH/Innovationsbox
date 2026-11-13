<?php

declare(strict_types=1);

namespace LemonbrainServices\Console\Command;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use LemonbrainServices\C5Backup\C5CreateAnArchiveProcess;
use LemonbrainServices\Console\Command as LemonbrainServicesCommand;

class C5CreateAnArchiveCommand extends LemonbrainServicesCommand
{
    public const CONFIG_NAME = 'lemonbrain:backup:archive';
    public const CONFIG_DESCRIPTION = 'Archives the backup directory and then deletes the directory after compressing';

    protected function configure()
    {
        $this->setName(self::CONFIG_NAME)
            ->setDescription(self::CONFIG_DESCRIPTION)
            ->addEnvOption();
    }

    /**
     * Run the back-up process command from C5 CLI
     * Create a target directory on the server
     * This then needs to be copied locally to the USB stick
     */
    public function handle()
    {
        $app = App::getFacadeApplication();
        $dirName = $this->getDirectoryNameQuestionChoices();
        $archiveProcess = $app->make(C5CreateAnArchiveProcess::class);
        $archiveProcess->start($dirName);
    }
}

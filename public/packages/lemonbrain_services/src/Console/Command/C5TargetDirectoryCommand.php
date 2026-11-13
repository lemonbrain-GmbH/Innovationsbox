<?php

declare(strict_types=1);

namespace LemonbrainServices\Console\Command;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use LemonbrainServices\C5Backup\C5TargetDirProcess;
use LemonbrainServices\Console\Command as LemonbrainServicesCommand;

class C5TargetDirectoryCommand extends LemonbrainServicesCommand
{
    public const CONFIG_NAME = 'lemonbrain:backup:mkdir';
    public const CONFIG_DESCRIPTION = 'Create a directory for the files and directories.';

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
        $mkdirProcess = $app->make(C5TargetDirProcess::class);
        $mkdirProcess->start();
    }
}

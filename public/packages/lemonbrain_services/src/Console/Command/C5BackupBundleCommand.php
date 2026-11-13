<?php

declare(strict_types=1);

namespace LemonbrainServices\Console\Command;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use Helpers\SymfonyFileSystemHelper;
use LemonbrainServices\C5Backup\C5BackupBundleProcess;
use LemonbrainServices\Console\Command as LemonbrainServicesCommand;

class C5BackupBundleCommand extends LemonbrainServicesCommand
{
    public const CONFIG_NAME = 'lemonbrain:backup:bundle';
    public const CONFIG_DESCRIPTION = 'Start backup process. Dump the database, copy files and directories. Compresses bundle.';

    protected function configure()
    {
        $this->setName(self::CONFIG_NAME)
            ->setDescription(self::CONFIG_DESCRIPTION)
            ->addEnvOption();
    }

    /**
     * Run the back-up process command from C5 CLI
     * Makes back-up directory, dumps the database and copies the files and directories
     */
    public function handle()
    {
        $app = App::getFacadeApplication();
        $sourcePath = $app->make(SymfonyFileSystemHelper::class)->createDirectoryInSourcePath();
        $backupProcess = $app->make(C5BackupBundleProcess::class);
        $backupProcess->start($sourcePath);
    }
}

<?php

declare(strict_types=1);

namespace LemonbrainServices\Console\Command;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use LemonbrainServices\C5Backup\C5CopyFilesAndDirectoriesProcess;
use LemonbrainServices\Console\Command as LemonbrainServicesCommand;
use Symfony\Component\Console\Input\InputOption;

class C5CopyFilesAndDirectoriesCommand extends LemonbrainServicesCommand
{
    public const CONFIG_NAME = 'lemonbrain:backup:copy-files-directories';
    public const CONFIG_DESCRIPTION = "Back up your site's files and directories, listed as arrays as your dot env file.";
    public const CONFIG_TARGET_DIR = 'target-directory';
    public const CONFIG_TARGET_DIR_SHORTCUT = 'target-directory';
    public const CONFIG_TARGET_DIR_DESCRIPTION = 'target-directory';

    protected function configure()
    {
        $this->setName(self::CONFIG_NAME)
            ->setDescription(self::CONFIG_DESCRIPTION)
            ->addEnvOption()
            ->addOption(self::CONFIG_TARGET_DIR, self::CONFIG_TARGET_DIR_SHORTCUT, InputOption::VALUE_OPTIONAL, self::CONFIG_TARGET_DIR_DESCRIPTION);
    }

    /**
     * Run the back-up process command from C5 CLI
     * Copies files and directories into chosen directory
     */
    public function handle()
    {
        $app = App::getFacadeApplication();
        $dirName = $this->getDirectoryNameQuestionChoices();
        $backupProcess = $app->make(C5CopyFilesAndDirectoriesProcess::class);
        $backupProcess->start($dirName);
    }
}

<?php

declare(strict_types=1);

namespace LemonbrainServices\Console\Command;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use LemonbrainServices\C5Backup\C5SqlDumpProcess;
use LemonbrainServices\Console\Command as LemonbrainServicesCommand;
use Symfony\Component\Console\Input\InputOption;

class C5SqlDumpCommand extends LemonbrainServicesCommand
{
    public const CONFIG_NAME = 'lemonbrain:backup:mysqldump';
    public const CONFIG_DESCRIPTION = "Back up your site's database.";
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
     */
    public function handle()
    {
        $app = App::getFacadeApplication();
        $dirName = $this->getDirectoryNameQuestionChoices();
        $sqlDumpProcess = $app->make(C5SqlDumpProcess::class);
        $sqlDumpProcess->start($dirName);
    }
}

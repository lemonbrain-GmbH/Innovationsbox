<?php

declare(strict_types=1);

namespace LemonbrainServices\Console;

use Concrete\Core\Console\Command as CoreCommand;
use Concrete\Core\Support\Facade\Application as App;
use Helpers\SymfonyFileSystemHelper;
use LemonbrainServices\C5Backup\C5TargetDirProcess;

class Command extends CoreCommand
{
    public const CHOOSE_DIR = 'Choose the directory from those listed below.';
    public const DIR_NAME_QUESTION = 'Would you like to use an existing directory in your target location?';
    public const DATE_FORMAT = 'Ymd';

    /**
     * @return string|null
     */
    protected function getDirectoryNameQuestionChoices(): ?string
    {
        $this->output->write('Looking for all directories...');

        $app = App::getFacadeApplication();
        $directoryNameQuestionChoices = $app->make(SymfonyFileSystemHelper::class)->getDirectoryNameQuestionChoices(date(self::DATE_FORMAT));
        $dirNames = [];
        foreach ($directoryNameQuestionChoices as $directoryNameQuestionChoiceValue) {
            if (file_exists($directoryNameQuestionChoiceValue)) {
                $dirNames[] = $directoryNameQuestionChoiceValue->getPathname();
            }
        }

        if (!is_array($dirNames) && count($dirNames) <= 0) {
            $mkdirProcess = $app->make(C5TargetDirProcess::class);
            $mkdirProcess->start();
        } else {
            if ($this->confirm(self::DIR_NAME_QUESTION)) {
                return $this->choice(self::CHOOSE_DIR, $dirNames);
            }
        }
        return null;
    }
}

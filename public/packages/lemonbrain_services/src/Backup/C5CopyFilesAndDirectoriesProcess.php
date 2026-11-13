<?php

declare(strict_types=1);

namespace LemonbrainServices\C5Backup;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use Helpers\SymfonyFileSystemHelper;

class C5CopyFilesAndDirectoriesProcess
{
    /**
     * Copy files, config directories
     * DIR_BASE or $_SERVER['DOCUMENT_ROOT'] or DIR_BASE_CORE or DIR_PACKAGES
     * Other options: DIR_APPLICATION
     */
    public function start($target = null)
    {
        $app = App::getFacadeApplication();
        $app->make(SymfonyFileSystemHelper::class)->createDirCopyFilesAndDirectories($target);
        $app->make(SymfonyFileSystemHelper::class)->createAppJsonFile($target);
    }
}

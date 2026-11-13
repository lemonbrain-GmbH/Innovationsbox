<?php

declare(strict_types=1);

namespace LemonbrainServices\C5Backup;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;

class C5BackupBundleProcess
{
    /**
     * @return void
     */
    public function start($target = null)
    {
        $app = App::getFacadeApplication();
        $backupProcess = $app->make(C5CopyFilesAndDirectoriesProcess::class);
        $backupProcess->start($target);
        $sqlDumpProcess = $app->make(C5SqlDumpProcess::class);
        $sqlDumpProcess->start($target);
        $archiveProcess = $app->make(C5CreateAnArchiveProcess::class);
        $archiveProcess->start($target);
    }
}

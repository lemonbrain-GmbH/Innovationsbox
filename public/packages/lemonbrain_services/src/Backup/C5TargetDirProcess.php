<?php

declare(strict_types=1);

namespace LemonbrainServices\C5Backup;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;

class C5TargetDirProcess
{
    public function start()
    {
        $app = App::getFacadeApplication();
        $app->make(\Helpers\SymfonyFileSystemHelper::class)->makeBackupDirectory();
    }
}

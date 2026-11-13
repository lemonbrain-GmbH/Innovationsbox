<?php

declare(strict_types=1);

namespace LemonbrainServices\C5Backup;

defined('C5_EXECUTE') or die('Access Denied.');

class C5CreateAnArchiveProcess
{
    public const TAR = '.tar';
    public const PHAR = 'phar';

    public function start($target = null)
    {
        $pos = strrpos($target, '/');
        $appDirectory = $pos === false ? $target : substr($target, $pos + 1);
        $appDirectoryTar = $appDirectory . '_' . rand(0, 99999);
        $sourcePath = getenv('SOURCE_PATH');
        $tarZipCommand = "tar  --remove-files -cvzf {$sourcePath}/{$appDirectoryTar}.tar.gz  -C {$sourcePath}/{$appDirectory} .";

        @exec($tarZipCommand);
    }
}

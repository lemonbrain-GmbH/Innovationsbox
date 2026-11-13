<?php

declare(strict_types=1);

namespace Helpers;

use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Support\Facade\Application as App;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SymfonyFileSystemHelper
{
    public const CACHE_DIR = 'cache';
    public const FILES_CACHE_DIR = 'files/cache';
    public const FROM_DIR_LOCATION = '..';
    public const COMPOSER = 'composer';
    public const APPLICATION = 'application';
    public const ENV = '.env';
    public const AUTH = 'auth.json';

    protected $fileSystem;
    protected $files;
    protected $directories;

    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
        $this->files = explode(',', getenv('FILES'));
        $this->directories = explode(',', getenv('DIRECTORIES'));
    }

    /**
     * @param $targetDirectory String|null the target directory where the files and directories should be copied to
     * @return void
     */
    public function createDirCopyFilesAndDirectories(string $targetDirectory = null)
    {
        self::makeBackupDirectory($targetDirectory);
        self::copyFiles($targetDirectory);
        self::copyDirectories($targetDirectory);
    }

    /**
     * @param $targetDirectory String|null the target directory where the files and directories should be copied to
     * @return void
     */
    public function makeBackupDirectory(string $targetDirectory = null)
    {
        if (!$targetDirectory) {
            $targetDirectory = $this->createDirectoryInSourcePath();
        }

        $this->fileSystem->mkdir($targetDirectory);
    }

    /**
     * @return string
     */
    public function createDirectoryInSourcePath(): string
    {
        $dateTime = new \DateTime();
        $date = $dateTime->format('YmdHi');
        return getenv('SOURCE_PATH') . DIRECTORY_SEPARATOR . $date;
    }

    /**
     * @param $targetDirectory String|null the target directory where the files and directories should be copied to
     * @return void
     */
    public function copyFiles(string $targetDirectory = null)
    {
        foreach ($this->files as $file) {
            // Special case for composer & env files
            if (str_starts_with($file, self::COMPOSER) || str_starts_with($file, self::ENV) || str_starts_with($file, self::AUTH)) {
                $sourcePath = DIR_BASE . DIRECTORY_SEPARATOR . self::FROM_DIR_LOCATION . DIRECTORY_SEPARATOR . $file;
            } else {
                $sourcePath = DIR_BASE . DIRECTORY_SEPARATOR . $file;
            }

            if (file_exists($sourcePath)) {
                $this->fileSystem->copy($sourcePath, $targetDirectory . DIRECTORY_SEPARATOR . $file);
            } else {
                $logger = App::getFacadeApplication()->make(LoggerFactory::class)->createLogger('backup');
                $logger->warning('Backup copyFiles: source file not found: ' . $sourcePath);
            }
        }
    }

    /**
     * @param $targetDirectory String|null the target directory where the files and directories should be copied to
     * @return void
     */
    public function copyDirectories(string $targetDirectory = null)
    {
        foreach ($this->directories as $dir) {
            if ($dir !== self::APPLICATION) {
                $sourceDir = DIR_APPLICATION . DIRECTORY_SEPARATOR . $dir;
            } else {
                $sourceDir = DIR_APPLICATION;
            }

            if (!is_dir($sourceDir)) {
                $logger = App::getFacadeApplication()->make(LoggerFactory::class)->createLogger('backup');
                $logger->warning('Backup copyDirectories: source directory not found: ' . $sourceDir);
                continue;
            }

            $this->fileSystem->mirror($sourceDir, $targetDirectory . DIRECTORY_SEPARATOR . $dir);

            if ($dir !== self::APPLICATION) {
                if (file_exists($targetDirectory . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . self::CACHE_DIR)) {
                    $this->fileSystem->remove($targetDirectory . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . self::CACHE_DIR);
                }
            } else {
                if (file_exists($targetDirectory . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . self::FILES_CACHE_DIR)) {
                    $this->fileSystem->remove($targetDirectory . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . self::CACHE_DIR);
                }
            }
        }
    }

    public function deleteDirectory(string $targetDirectory)
    {
        $this->fileSystem->remove($targetDirectory);
    }

    /**
     * @param string $date
     * @return Finder
     */
    public function getDirectoryNameQuestionChoices(string $date): Finder
    {
        $finder = new Finder();
        $finder->directories()->name($date . '*')->in(getenv('SOURCE_PATH'));
        return $finder;
    }

    /**
     * Dump DB into target directory
     */
    public function createAppJsonFile($targetPath = null)
    {
        $username = getenv('DB_USERNAME');
        $database = getenv('DB_DATABASE');
        $password = getenv('DB_PASSWORD');
        $dbHostname = getenv('DB_HOSTNAME');
        $c5Version = getenv('C5_VERSION');
        $gitUserFirstName = getenv('GIT_USER_FIRST_NAME');
        $gitUserLastName = getenv('GIT_USER_LAST_NAME');
        $gitUserEmail = getenv('GIT_USER_EMAIL');

        $site = App::getFacadeApplication()->make('site')->getSite();

        $jsonFileContents = [
            'apps' => [
                [
                    'appName'  => strtolower(str_replace(' ', '_', $site->getSiteName())),
                    'dbUsername' => $username,
                    'dbPassword' => $password,
                    'dbDatabase' => $database,
                    'dbHostname' => $dbHostname,
                    'c5Version' => $c5Version,
                    'gitUserFirstName' => $gitUserFirstName,
                    'gitUserLastName' => $gitUserLastName,
                    'gitUserEmail' => $gitUserEmail,
                ],
            ],
        ];

        file_put_contents("{$targetPath}/app.json", json_encode($jsonFileContents));
    }
}

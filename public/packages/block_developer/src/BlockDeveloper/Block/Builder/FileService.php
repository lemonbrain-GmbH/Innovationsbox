<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block\Builder;

use Concrete\Core\File\Service\File;
use Illuminate\Filesystem\Filesystem;

class FileService
{
    /**
     * @var File
     */
    protected $file;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct()
    {
        $this->file = new File();
        $this->filesystem = new Filesystem();
    }

    /**
     * @param      $fileName
     * @param null $contents
     * @param bool $clear
     *
     * @return bool
     */
    public function writeFile($fileName, $contents = null, $clear = true)
    {
        if ($clear) {
            $this->file->clear($fileName);
        }

        return $this->file->append($fileName, $contents);
    }

    /**
     * @param       $fileName
     * @param array $search
     * @param array $replace
     *
     * @return bool
     */
    public function replaceInFile($fileName, $search = [], $replace = [])
    {
        $contents = $this->file->getContents($fileName);
        $this->file->clear($fileName);

        return $this->file->append($fileName, str_replace($search, $replace, $contents));
    }

    /**
     * @param      $path
     * @param int  $mode
     * @param bool $recursive
     * @param bool $force
     *
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        return $this->filesystem->makeDirectory($path, $mode, $recursive, $force);
    }
}

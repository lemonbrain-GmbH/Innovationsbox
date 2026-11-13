<?php

declare(strict_types=1);

namespace Concrete\Package\BlockDeveloper;

use Concrete\Core\Block\BlockType\BlockTypeList;
use Concrete\Core\Package\Package;
use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper;
use Exception;

class Controller extends Package
{
    protected $pkgHandle = 'block_developer';
    protected $pkgAutoloaderRegistries = ['src' => 'Devoda\\BlockDeveloper'];
    protected $pkgVersion = '1.4.8';
    protected $appVersionRequired = '9.2.0';

    public function getPackageName()
    {
        return t('Block Developer');
    }

    public function getPackageDescription()
    {
        return t('Develop your Block Types, based on a config(.php) file');
    }

    public function on_start()
    {
        $legacy = version_compare(Config::get('concrete.version'), '8.2', '<');
        if ($legacy) {
            $this->app = \Concrete\Core\Support\Facade\Facade::getFacadeApplication();
        }
        if ($this->app->isRunThroughCommandLineInterface()) {
            $console = $this->app->make('console');
            if (!$legacy) {
                $this->strictLoaderAdd('Devoda\\BlockDeveloper', DIR_PACKAGES . '/block_developer/src_mv/8.2');
                $console->add(new \Devoda\BlockDeveloper\Console\Command\BlockDeveloperCommand());
            } else {
                $this->strictLoaderAdd('Devoda\\BlockDeveloper\\Legacy', DIR_PACKAGES . '/block_developer/src_mv/Legacy');
                $console->add(new \Devoda\BlockDeveloper\Legacy\Console\Command\BlockDeveloperCommand());
            }
        }
    }

    public function uninstall()
    {
        $btHandles = [];
        $btl = new BlockTypeList();
        $btl->includeInternalBlockTypes();
        foreach ($btl->get() as $bt) {
            /* @var \Concrete\Core\Block\Block $bt */
            $btHandle = $bt->getBlockTypeHandle();
            if ($btController = BlockDeveloper::getBlockTypeController($btHandle)) {
                $btControllerParentClass = get_parent_class($btController);
                if ($btControllerParentClass == 'Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController') {
                    $btHandles[] = $btHandle;
                }
            }
        }
        // If we still have Block Types installed, using the Block Developer "BlockController", it's not OK to uninstall
        if (!empty($btHandles)) {
            throw new Exception(t('There still are Block Types installed, which require this Add-On. Please uninstall these first, to be able to uninstall this Add-On. The Block Type handles are: %s', implode(', ', $btHandles)));
        } else {
            parent::uninstall();
        }
    }

    protected function strictLoaderAdd($prefix, $baseDir)
    {
        $strictLoader = new \Symfony\Component\ClassLoader\Psr4ClassLoader();
        $strictLoader->addPrefix($prefix, $baseDir);
        $strictLoader->register();
    }
}

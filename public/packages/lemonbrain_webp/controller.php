<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainWebp;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Application\Application;
use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\File\Event\ThumbnailDelete;
use Concrete\Core\File\Event\ThumbnailGenerate;
use Concrete\Core\Job\Job;
use Concrete\Core\Package\Package;
use LemonbrainWebp\Handler\ThumbnailEventHandler;
use LemonbrainWebp\ImageBrewer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Controller extends Package
{
    /**
     * The container instance to be used to build dependencies.
     *
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    protected $pkgHandle = 'lemonbrain_webp';
    protected $appVersionRequired = '9.1.1';
    protected $pkgVersion = '2.0.1';
    protected $pkg;
    protected $pkgAutoloaderRegistries = [
        'src' => 'LemonbrainWebp',
    ];

    public function getPackageDescription()
    {
        return t('Creates webp files to thumbnail images defined in this package and has a really nice and easy to use API to get the images to the html');
    }

    public function getPackageName()
    {
        return t('Lemonbrain Webp');
    }

    /**
     * Initialize the instance.
     *
     * @param Application $app the application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $app->singleton(ThumbnailEventHandler::class);
        $app->singleton(ImageBrewer::class);
    }

    public function on_start()
    {
        $dispatcher = $this->app->make(EventDispatcherInterface::class);

        $dispatcher->addListener(
            'on_thumbnail_generate',
            function (ThumbnailGenerate $event) {
                $this->app->make(ThumbnailEventHandler::class)->handleGenerate($event);
            }
        );

        $dispatcher->addListener(
            'on_thumbnail_delete',
            function (ThumbnailDelete $event) {
                $this->app->make(ThumbnailEventHandler::class)->handleDelete($event);
            }
        );

        $al = AssetList::getInstance();
        $al->register('css', 'lemonbrain_webp', 'css/min/lemonbrain_webp.min.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => true], $this);
        $al->register('css', 'lemonbrain_webp_new', 'css/min/lemonbrain_webp_new.min.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => true], $this);
    }

    public function install()
    {
        parent::install();

        $this->configureThumbnailTypes();
        $this->installAutomatedJobs();
    }

    public function upgrade()
    {
        $result = parent::upgrade();

        $this->deleteOldThumbnailTypes();
        $this->configureThumbnailTypes();
        $this->installAutomatedJobs();

        return $result;
    }

    private function configureThumbnailTypes()
    {
        $thumbnails = ThumbnailEventHandler::getThumbnails();
        foreach ($thumbnails as $thumbnailHandle => $thumbnail) {
            $this->installThumbnailType($thumbnail['name'], $thumbnailHandle, $thumbnail['width']);
        }
    }

    private function installAutomatedJobs()
    {
        if (!is_object(Job::getByHandle('create_webp_thumbnails'))) {
            Job::installByPackage('create_webp_thumbnails', $this);
        }
    }

    private function deleteOldThumbnailTypes()
    {
        $thumbnails = [
            'webp_xsmall',
            'webp_small',
            'webp_medium',
            'webp_large',
            'webp_xlarge',
        ];

        foreach ($thumbnails as $thumbnail) {
            $thumbnail = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($thumbnail);
            if ($thumbnail) {
                $thumbnail->delete();
            }
        }
    }

    private function installThumbnailType($name, $handler, $width, $height = '', $exact = false)
    {
        $thumbnail = \Concrete\Core\File\Image\Thumbnail\Type\Type::getByHandle($handler);
        if (is_object($thumbnail)) {
            $thumbnail->delete();
        }
        $thumbnail = new \Concrete\Core\Entity\File\Image\Thumbnail\Type\Type();
        $thumbnail->setHandle($handler);
        $thumbnail->setName($name);
        $thumbnail->setWidth($width);
        $thumbnail->setHeight($height);
        if ($exact) {
            $thumbnail->setSizingMode('exact');
        }
        $thumbnail->save();
    }
}

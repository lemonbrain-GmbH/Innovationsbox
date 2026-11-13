<?php

declare(strict_types=1);

namespace LemonbrainWebp\Handler;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\File\Event\ThumbnailDelete;
use Concrete\Core\File\Event\ThumbnailGenerate;
use Concrete\Core\File\StorageLocation\StorageLocationFactory;
use Concrete\Core\Support\Facade\Log;
use League\Flysystem\FileNotFoundException;

class ThumbnailEventHandler
{
    protected static $thumbnails = [
        'webp_small' => [
            'name' => 'Small',
            'handler' => 'webp_small',
            'width' => 300,
        ],
        'webp_medium' => [
            'name' => 'Medium',
            'handler' => 'webp_medium',
            'width' => 1200,
        ],
        'webp_large' => [
            'name' => 'Large',
            'handler' => 'webp_large',
            'width' => 1800,
        ],
    ];

    /**
     * Initialize the instance.
     *
     * @param Application $app the application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getThumbnails()
    {
        return self::$thumbnails;
    }

    public function handleGenerate(ThumbnailGenerate $event)
    {
        return $this->generateWebpThumbnail($event->getPath());
    }

    public function handleDelete(ThumbnailDelete $event)
    {
        return $this->deleteWebpThumbnail($event->getPath());
    }

    public function generateWebpThumbnail(string $originalPath)
    {
        $this->deleteWebpThumbnail($originalPath);

        try {
            $webpPath = '';
            $originalThumbnailFormat = pathinfo($originalPath, PATHINFO_EXTENSION);
            if ($originalThumbnailFormat !== 'webp') {
                $config = $this->app->make(Repository::class);
                $webpPath = str_replace($originalThumbnailFormat, 'webp', $originalPath);
                $fso = $this->app->make(StorageLocationFactory::class)->fetchDefault()->getFileSystemObject();

                $content = $fso->read($originalPath);

                switch ($originalThumbnailFormat) {
                    case 'jpeg':
                    case 'jpg':
                        $contentImage = imagecreatefromstring($content);
                        break;

                    case 'png':
                        $contentImage = imagecreatefromstring($content);
                        imagepalettetotruecolor($contentImage);
                        imagealphablending($contentImage, true);
                        imagesavealpha($contentImage, true);
                        break;

                    case 'gif':
                        $contentImage = imagecreatefromstring($content);
                        break;

                    default:
                }

                $webp_quality = (int) ($config->get('concrete.misc.default_webp_image_compression') ?? 80);
                imagewebp($contentImage, DIR_FILES_UPLOADED_STANDARD . $webpPath, $webp_quality);

                imagedestroy($contentImage);

                gc_collect_cycles();
            }
            return true;
        } catch (\Exception $e) {
            Log::error(t('generate webp thumbnail error %s, tried this path %s', $e->getMessage(), $webpPath));
        }
    }

    private function deleteWebpThumbnail(string $originalPath)
    {
        try {
            $webpPath = '';
            $originalThumbnailFormat = pathinfo($originalPath, PATHINFO_EXTENSION);
            if ($originalThumbnailFormat !== 'webp') {
                $fso = $this->app->make(StorageLocationFactory::class)->fetchDefault()->getFileSystemObject();

                $webpPath = str_replace($originalThumbnailFormat, 'webp', $originalPath);

                if ($fso->has($webpPath)) {
                    $fso->delete($webpPath);
                }
            }
            return true;
        } catch (FileNotFoundException $e) {
            Log::info(t('delete webp thumbnail not found, maybe was not yet created %s, tried this path %s', $e->getMessage(), $webpPath));
        } catch (\Exception $e) {
            Log::error(t('delete webp thumbnail error %s, tried this path %s', $e->getMessage(), $webpPath));
        }
    }
}

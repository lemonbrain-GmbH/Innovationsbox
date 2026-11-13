<?php

declare(strict_types=1);

namespace LemonbrainWebp;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Application\Application as App;
use Concrete\Core\Entity\File\File as FileEntity;
use Concrete\Core\File\File;
use Concrete\Core\File\Image\Thumbnail\Type\Type as ThumbType;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Page\Page;
use LemonbrainWebp\Handler\ThumbnailEventHandler;

class ImageBrewer
{
    public const DIRECTORY_SEPERATOR = '/';

    private $objectFitClassMapper = [
        'fill' => 'lb-object-fit-fill',
        'cover' => 'lb-object-fit-cover',
        'contain' => 'lb-object-fit-contain',
        'none' => 'lb-object-fit-none',
        'scale-down' => 'lb-object-fit-scale-down',
    ];

    private $dirBase = '';

    /**
     * The container instance to be used to build dependencies.
     *
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * Initialize the instance.
     *
     * @param Application $app the application instance
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->dirBase = DIR_BASE;

        if ($app['app_relative_path'] !== '') {
            $this->dirBase = explode($app['app_relative_path'], $this->dirBase)[0];
        }
    }

    /**
     * @param FileEntity $image
     * @param string $alt
     * @param string $ratio 4/3, 16/9, ..., auto
     * @param string $objectFit 'fill', 'cover', 'contain', 'none', 'scale-down'
     */
    public function getFromFile(
        FileEntity $image,
        string $alt,
        string $displaySizes,
        string $ratio = 'auto',
        string $objectFit = 'scale-down',
        array $miscFields = [],
        string $additionalClasses = '',
        int $width = 0,
        int $height = 0,
        bool $lazyLoading = false,
    ) {
        if (strtolower($image->getExtension()) === 'svg') {
            return '
            <div class="lb-img-wrapper ' . $additionalClasses . '" style="aspect-ratio: ' . $ratio . '">
                <picture>
                    <source type="image/svg+xml" srcset="' . $image->getURL() . '">
                    <img ' . ($height > 0 ? 'height="' . $height . '"' : '') . ' ' . ($width > 0 ? 'width="' . $width . '"' : '') . ' ' . ($lazyLoading ? 'loading="lazy"' : '') . ' src="' . $image->getURL() . '" alt="' . $alt . '" ' . $this->parseMiscFields($this->objectFitClassMapper[$objectFit] . ' lb-object-fit ' . ($ratio !== 'auto' ? 'lb-object-absolute' : '') . ' ' . $additionalClasses, $miscFields) . '>
                </picture>
            </div>
            ';
        } elseif (
            strtolower($image->getExtension()) === 'jpg' ||
            strtolower($image->getExtension()) === 'jpeg' ||
            strtolower($image->getExtension()) === 'png' ||
            strtolower($image->getExtension()) === 'gif' ||
            strtolower($image->getExtension()) === 'webp'
        ) {
            $search = $image->getExtension();
            if (
                strtolower($image->getExtension()) === 'jpg' ||
                strtolower($image->getExtension()) === 'jpeg'
            ) {
                $search = ['jpg', 'jpeg'];
            }
            $thumbnails = ThumbnailEventHandler::getThumbnails();

            $webps = '';
            $others = '';

            foreach ($thumbnails as $thumbHandle => $thumbnail) {
                $type = ThumbType::getByHandle($thumbHandle);
                if (isset($type)) {
                    $widthFromSystem = $type->getWidth();
                    $heightFromSystem = $type->getHeight();

                    $homePage = Page::getByID(1);
                    $homePageAbsoluteLink = $homePage->getCollectionLink();

                    $src = $image->getThumbnailURL($type->getBaseVersion());
                    $fileSrc = str_replace($homePageAbsoluteLink, '', $src);
                    if ($src) {
                        $webpSrc = str_replace($search, 'webp', $src);
                        $webpFileSrc = str_replace($search, 'webp', $fileSrc);

                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $webpFileSrc)) {
                            if ($webps === '') {
                                $webps = $webpSrc . ' ' . (string) $widthFromSystem . 'w';
                            } else {
                                $webps .= ',' . $webpSrc . ' ' . (string) $widthFromSystem . 'w';
                            }
                        }
                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $fileSrc)) {
                            if ($others === '') {
                                $others = $src . ' ' . (string) $widthFromSystem . 'w';
                            } else {
                                $others .= ',' . $src . ' ' . (string) $widthFromSystem . 'w';
                            }
                        }
                    }

                    $srcDbl = $image->getThumbnailURL($type->getDoubledVersion());
                    $fileSrcDbl = str_replace($homePageAbsoluteLink, '', $srcDbl);
                    if ($srcDbl) {
                        $webpSrcDbl = str_replace($search, 'webp', $srcDbl);
                        $webpFileSrcDbl = str_replace($search, 'webp', $fileSrcDbl);
                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $webpFileSrcDbl)) {
                            if ($webps === '') {
                                $webps .= $webpSrcDbl . ' ' . (string) $widthFromSystem * 2 . 'w';
                            } else {
                                $webps .= ',' . $webpSrcDbl . ' ' . (string) $widthFromSystem * 2 . 'w';
                            }
                        }
                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $fileSrcDbl)) {
                            if ($others === '') {
                                $others .= $srcDbl . ' ' . (string) $widthFromSystem * 2 . 'w';
                            } else {
                                $others .= ',' . $srcDbl . ' ' . (string) $widthFromSystem * 2 . 'w';
                            }
                        }
                    }
                }
            }

            $isALandscapeImage = $widthFromSystem > $heightFromSystem ? true : false;

            return '<div class="lb-img-wrapper ' . $additionalClasses . '" ' . 'style="aspect-ratio: ' . $ratio . ';">
                <picture>' .
                    ($image->getExtension() !== 'gif' ?
                    '<source media="(orientation: landscape)" type="image/webp" srcset="' . $webps . '" sizes="' . $displaySizes . '">' : '') .
                    '<source media="(orientation: landscape)" type="image/' . $image->getExtension() . '" srcset="' . $others . '" sizes="' . $displaySizes . '">' .
                    '<img ' . ($height > 0 ? 'height="' . $height . '"' : '') . ' ' . ($width > 0 ? 'width="' . $width . '"' : '') . ' ' . ($lazyLoading ? 'loading="lazy"' : '') . ' src="' . $image->getURL() . '" alt="' . $alt . '" ' . $this->parseMiscFields($this->objectFitClassMapper[$objectFit] . ' lb-object-fit ' . ($ratio !== 'auto' ? 'lb-object-absolute' : '') . ' ' . $additionalClasses, $miscFields) . '>
                </picture>
            </div>';
        }
    }

    /**
     * @param int $imageId
     * @param string $alt
     * @param string $ratio 4:3, 16:9, ..., auto
     * @param string $objectFit 'fill', 'cover', 'contain', 'none', 'scale-down'
     */
    public function get(
        int $imageId,
        string $alt,
        string $displaySizes,
        string $ratio = 'auto',
        string $objectFit = 'scale-down',
        array $miscFields = [],
        string $additionalClasses = '',
        int $width = 0,
        int $height = 0,
        bool $lazyLoading = false,
    ) {
        if ($imageId > 0) {
            $image = File::getByID(intval($imageId));

            if ($image instanceof FileEntity) {
                return $this->getFromFile(
                    image: $image,
                    alt: $alt,
                    displaySizes: $displaySizes,
                    ratio: $ratio,
                    objectFit: $objectFit,
                    miscFields: $miscFields,
                    additionalClasses: $additionalClasses,
                    width: $width,
                    height: $height,
                    lazyLoading: $lazyLoading
                );
            }
        }

        return 'unfortunatly we couldnt find the image your are looking for';
    }

    public function getJson(int $imageId, $enableAddContent = false)
    {
        if ($imageId > 0) {
            $image = File::getByID(intval($imageId));
            $thumbnails = ThumbnailEventHandler::getThumbnails();
            $fileService = $this->app->make(FileService::class);
            $content = '';

            if ($image instanceof FileEntity) {
                if ($enableAddContent) {
                    $content = $fileService->getContents($this->dirBase . File::getRelativePathFromID($imageId));
                }
                if (strtolower($image->getExtension()) === 'svg') {
                    $thumbnails = array_map(function ($thumbnail) use ($image) {
                        $thumbnail['url'] = $image->getURL();
                        $thumbnail['url_2x'] = $image->getURL();
                        return $thumbnail;
                    }, $thumbnails);
                } elseif (
                    strtolower($image->getExtension()) === 'jpg' ||
                    strtolower($image->getExtension()) === 'jpeg' ||
                    strtolower($image->getExtension()) === 'png' ||
                    strtolower($image->getExtension()) === 'gif'
                ) {
                    $search = $image->getExtension();
                    if (
                        strtolower($image->getExtension()) === 'jpg' ||
                        strtolower($image->getExtension()) === 'jpeg'
                    ) {
                        $search = ['jpg', 'jpeg'];
                    }

                    $homePage = Page::getByID(1);
                    $homePageAbsoluteLink = $homePage->getCollectionLink();

                    $thumbnails = array_map(function ($thumbnail) use ($image, $search, $homePageAbsoluteLink) {
                        $type = ThumbType::getByHandle($thumbnail['handler']);

                        $src = $image->getThumbnailURL($type->getBaseVersion());
                        $fileSrc = str_replace($homePageAbsoluteLink, '', $src);

                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $fileSrc)) {
                            $thumbnail['url'] = $src;
                        }

                        $webpSrc = str_replace($search, 'webp', $src);
                        $webpFileSrc = str_replace($search, 'webp', $fileSrc);

                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $webpFileSrc)) {
                            $thumbnail['url_webp'] = $webpSrc;
                        }

                        $srcDbl = $image->getThumbnailURL($type->getDoubledVersion());
                        $fileSrcDbl = str_replace($homePageAbsoluteLink, '', $srcDbl);
                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $fileSrcDbl)) {
                            $thumbnail['url_2x'] = $srcDbl;
                        }

                        $webpSrcDbl = str_replace($search, 'webp', $srcDbl);
                        $webpFileSrcDbl = str_replace($search, 'webp', $fileSrcDbl);

                        if (file_exists($this->dirBase . self::DIRECTORY_SEPERATOR . $webpFileSrcDbl)) {
                            $thumbnail['url_webp_2x'] = $webpSrcDbl;
                        }
                        return $thumbnail;
                    }, $thumbnails);
                }
                array_push($thumbnails, [
                    'name' => 'Source',
                    'handler' => 'source',
                    'width' => $image->getAttribute('width'),
                    'url' => $image->getURL(),
                    'content' => $content,
                ]);
            }
        } else {
            $thumbnails = 'unfortunatly we couldnt find the image your are looking for';
        }

        return json_encode($thumbnails);
    }

    /**
     * @param FileEntity $image
     * Returns the file content of the provided image
     */
    public function getInlineSvgFromFile(FileEntity $image)
    {
        $inline = '';
        if ($image && $image->getExtension() === 'svg') {
            $inline = file_get_contents(DIR_BASE . File::getRelativePathFromID($image->getFileID()));
        } else {
            $inline = '<div style="color: #842029; background-color: #f8d7da; border-color: #f5c2c7; padding: 1rem 1rem;">' . t('The provided File does not exist or is no SVG.') . '</div>';
        }
        return $inline;
    }

    /**
     * @param int $imageId
     * Returns the file content of the provided image id
     */
    public function getInlineSvgFromFileId(int $imageId)
    {
        $image = File::getByID(intval($imageId));
        return $this->getInlineSvgFromFile($image);
    }

    /**
     * Create an HTML fragment of attribute values, merging any CSS class names as necessary.
     *
     * @param string $defaultClass Default CSS class name
     * @param array $attributes a hash array of attributes (name => value), possibly including 'class'
     *
     * @return string A fragment of attributes suitable to put inside of an HTML tag
     */
    private function parseMiscFields($defaultClass, $attributes)
    {
        $attributes = (array) $attributes;
        if ($defaultClass) {
            $attributes['class'] = trim((isset($attributes['class']) ? $attributes['class'] : '') . ' ' . $defaultClass);
        }
        $attr = '';
        foreach ($attributes as $k => $v) {
            $attr .= " $k=\"$v\"";
        }

        return $attr;
    }
}

<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Entity\File\Version|null $image */
/** @var string $content */

$imageSrc = '';
$imageAlt = '';
if (is_object($image) && $image->getFileID()) {
    $imageSrc = (string) $image->getRelativePath();
    $imageAlt = (string) $image->getTitle();
}

$props = [
    'imageSrc' => $imageSrc,
    'imageAlt' => $imageAlt,
    'content' => (string) ($content ?? ''),
];

include DIR_COMPONENTS . '/molecules/info-panel/info-panel.php';

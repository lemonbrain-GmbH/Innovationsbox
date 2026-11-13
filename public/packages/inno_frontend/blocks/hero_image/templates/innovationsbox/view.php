<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Application\Application $app */
/** @var \Concrete\Core\Entity\File\Version|null $image */
/** @var object|null $button */
/** @var string|null $buttonColor */
/** @var string|null $title */
/** @var string|null $body */
/** @var string|null $altText */

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

if ($image === null) {
    return;
}

$buttonColor = $buttonColor ?? null;

// Extract button label and href from the CMS button object
$buttonLabel = '';
$buttonHref = '#';
if (isset($button)) {
    $buttonLabel = strip_tags((string) $button->getValue());
    $buttonHref = (string) ($button->getAttribute('href') ?? '#');
}

// Get image URL
$imageSrc = '';
if (is_object($image) && $image->getFileID()) {
    $imageSrc = (string) $image->getRelativePath();
}

$props = [
    'title' => (string) ($title ?? ''),
    'body' => (string) ($body ?? ''),
    'imageSrc' => $imageSrc,
    'imageAlt' => (string) ($altText ?? ''),
    'buttonLabel' => $buttonLabel,
    'buttonHref' => $buttonHref,
];

include DIR_COMPONENTS . '/organisms/hero-image/hero-image.php';

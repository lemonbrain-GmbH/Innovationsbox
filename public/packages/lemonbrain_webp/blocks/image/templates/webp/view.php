<?php defined('C5_EXECUTE') or die('Access Denied.');

use LemonbrainWebp\ImageBrewer;

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();

if (is_object($f) && $f->getFileID()) {
    $classes = '';
    $misc = [];
    $ratio = 'auto';
    $objectFit = 'contain';

    if ($maxWidth > 0 && $maxHeight > 0) {
        $ratio = '16:' . (16 / $maxWidth * $maxHeight);
        $objectFit = 'cover';
    }

    if ($f->getTypeObject()->isSVG()) {
        $classes .= ' ccm-svg';
    }

    $classes .= ' ccm-image-block img-responsive bID-' . $bID;

    if (!$altText) {
        $altText = '';
    }

    if ($linkURL) {
        echo '<a href="' . $linkURL . '" ' . ($openLinkInNewWindow ? 'target="_blank" rel="noopener noreferrer"' : '') . '>';
    }

    // add data attributes for hover effect
    if (is_object($foS) && !$f->getTypeObject()->isSVG() && !$foS->getTypeObject()->isSVG()) {
        $classes .= ' ccm-image-block-hover';
        $misc = [
            'data-default-src' => $imgPaths['default'],
            'data-hover-src' => $imgPaths['hover'],
        ];
    }

    echo $app->make(ImageBrewer::class)->get($f->getFileID(), h($altText), '100vw', $ratio, $objectFit, $misc, $classes);

    if ($linkURL) {
        echo '</a>';
    }
} elseif ($c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Empty Image Block.'); ?></div>
<?php
}

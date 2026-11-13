<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Application\Application $app */
/** @var \Concrete\Core\Page\Page $c */
/** @var mixed $th */
/** @var \Concrete\Block\PageList\Controller $controller */
/** @var \Concrete\Core\Page\Page[] $pages */

$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$c = Page::getCurrentPage();
$th = Core::make('helper/text');

if (is_object($c) && $c->isEditMode() && $controller->isBlockEmpty()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?php echo t('Empty Page List Block.') ?></div>
<?php } else {
    $pageData = [];
    foreach ($pages as $page) {
        $title = (string) $page->getCollectionName();
        $thumbnail = $page->getAttribute('thumbnail');
        $target = '_self';

        if ($page->getCollectionPointerExternalLink() != '') {
            $url = (string) $page->getCollectionPointerExternalLink();
            if ($page->openCollectionPointerExternalLinkInNewWindow()) {
                $target = '_blank';
            }
        } else {
            $url = (string) $page->getCollectionLink();
            $target = (string) ($page->getAttribute('nav_target') ?: '_self');
        }

        $imageSrc = '';
        if (is_object($thumbnail)) {
            $imageSrc = (string) $thumbnail->getRelativePath();
        }

        $pageData[] = [
            'title' => $title,
            'href' => $url,
            'target' => $target,
            'imageSrc' => $imageSrc,
            'imageAlt' => $title,
        ];
    }

    $props = ['pages' => json_encode($pageData)];
    include DIR_COMPONENTS . '/organisms/page-list-grid/page-list-grid.php';
} ?>

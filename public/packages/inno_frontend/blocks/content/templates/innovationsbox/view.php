<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var string $content */
/** @var \Concrete\Core\Page\Page $c */

$c = \Concrete\Core\Page\Page::getCurrentPage();
if (!$content && is_object($c) && $c->isEditMode()) { ?>
    <div class="ccm-edit-mode-disabled-item"><?=t('Empty Content Block.')?></div>
<?php
} else {
    $props = ['content' => (string) ($content ?? '')];
    include DIR_COMPONENTS . '/organisms/content-block/content-block.php';
}

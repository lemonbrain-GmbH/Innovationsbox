<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');

/** @var \Concrete\Core\Form\Service\Form $form */
$form = app('helper/form');
/** @var \Concrete\Core\Application\Service\FileManager $fileManager */
$fileManager = app('helper/concrete/file_manager');
/** @var \Concrete\Core\Editor\EditorInterface $editor */
$editor = app('editor');

/** @var int|null $fID */
/** @var string $content */
?>

<div class="form-group">
    <label class="control-label" for="fID"><?= t('Bild') ?></label>
    <?= $fileManager->image('fID', 'fID', t('Bild auswählen'), $fID ? \Concrete\Core\File\File::getByID($fID) : null) ?>
</div>

<div class="form-group">
    <label class="control-label"><?= t('Inhalt') ?></label>
    <?= $editor->outputBlockEditModeEditor('content', $content ?? '') ?>
</div>

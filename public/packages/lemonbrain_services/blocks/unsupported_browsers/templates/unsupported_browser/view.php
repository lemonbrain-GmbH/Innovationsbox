<?php defined('C5_EXECUTE') or die('Access Denied.');


$c = Page::getCurrentPage();
if ($c->isEditMode()) {
    $loc = Localization::getInstance();
    $loc->pushActiveContext(Localization::CONTEXT_UI);
    ?>
    <div class="ccm-edit-mode-disabled-item" style="width: <?php echo $width ?? '100%'; ?>; height: <?php echo $height ?? '40px'; ?>">
        <div style="padding: 0px 0px 0px 0px"><?= t('Unsupported browsers disabled in edit mode.') ?></div>
    </div>
<?php
        $loc->popActiveContext();
} else { ?>
    <div id="unsuppoerted-browser-<?= $bID ?>" class="ie-message" hidden>
        <div class="backdrop"></div>
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-12 col-md-10 col-lg-8">
                    <div class="text-wrapper">
                        <?php if (isset($description_1) && trim($description_1) != '') { ?>
                            <?php echo $description_1; ?>
                        <?php } ?>
                        <div class="close-button"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('spaInitDone', function() {
            if (window.spa && window.spa.UnsupportedBrowsers) {
                window.spa.unsupportedBrowser<?= $bID ?> = new window.spa.UnsupportedBrowsers();
                window.spa.unsupportedBrowser<?= $bID ?>.initModule(document.getElementById('unsuppoerted-browser-<?= $bID ?>'));
            }
        });
    </script>
<?php  } ?>

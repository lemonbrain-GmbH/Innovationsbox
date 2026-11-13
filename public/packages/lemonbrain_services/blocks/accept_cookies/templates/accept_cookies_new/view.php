<?php defined('C5_EXECUTE') or die('Access Denied.');

View::getInstance()->requireAsset('css', 'bootstrap4');
View::getInstance()->requireAsset('javascript', 'lb');
View::getInstance()->requireAsset('javascript', 'lb-util');
View::getInstance()->requireAsset('javascript', 'lb-util-uriParam');
View::getInstance()->requireAsset('css', 'font-awesome');
View::getInstance()->requireAsset('accept_cookies');

$c = Page::getCurrentPage();
if ($c->isEditMode()) {
    $loc = Localization::getInstance();
    $loc->pushActiveContext(Localization::CONTEXT_UI);
    ?>
    <div class="ccm-edit-mode-disabled-item" style="width: <?php echo $width ?? '100%'; ?>; height: <?php echo $height ?? '40px'; ?>">
        <div style="padding: 0px 0px 0px 0px"><?= t('Accept cookies disabled in edit mode.') ?></div>
    </div>
<?php
        $loc->popActiveContext();
} else { ?>
    <div id="accept-cookies-wrapper-<?= $bID ?>" class="accept-cookies-wrapper">
        <div class="backdrop"></div>
        <div class="hint" style="display:none">
            <div class="close">
                <i class="icon icon-schliessen-1"></i>
            </div>
            <div class="text-container">
                <?php if (isset($cookie_hint_title) && trim($cookie_hint_title) != '') { ?>
                    <h3><?php echo $cookie_hint_title; ?></h3>
                <?php } ?>
                <?php
                if (isset($cookie_hint) && trim($cookie_hint) != '') {
                    // checks if the hint comes from wysiwyg, if not enclose in p tag
                    if ($cookie_hint === strip_tags($cookie_hint)) {
                        $cookie_hint = '<p>' . $cookie_hint . '</p>';
                    }
                    ?>
                    <?php echo $cookie_hint; ?>
                <?php } ?>
                    <a class="settings" href="javascript:void(0);" target="_self"><?= t('Einstellungen') ?></a><?= '.' ?>
            </div>
        </div>
        <div class="popup" style="display: none;">
            <div class="text-container">
                <?php if (isset($cookies_setting_description) && trim($cookies_setting_description) != '') { ?>
                    <?php echo $cookies_setting_description; ?>
                <?php } ?>
                <?php if (is_array($cookies) && !empty($cookies)) { ?>
                    <ul>
                        <?php foreach ($cookies as $cookies_item_key => $cookies_item) { ?>
                            <li>

                                <?php if (isset($cookies_item['cookie_handler']) && trim($cookies_item['cookie_handler']) != '') { ?>
                                    <?php $cookieHandler = h($cookies_item['cookie_handler']); ?>
                                <?php } ?>

                                <input class="form-check-input form-element" type="checkbox" name="<?= $cookieHandler ?>" id="<?= $cookieHandler ?>" value="<?= $cookieHandler ?>" checked="checked">
                                <label class="form-check-label" for="<?= $cookieHandler ?>">
                                    <?php if (isset($cookies_item['cookie_title']) && trim($cookies_item['cookie_title']) != '') { ?>
                                        <b><?php echo h($cookies_item['cookie_title']); ?></b>
                                    <?php } ?>
                                </label>
                                <?php if (isset($cookies_item['cookie_description']) && trim($cookies_item['cookie_description']) != '') { ?>
                                    <?php echo $cookies_item['cookie_description']; ?>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
            <div class="buttons">
                <a class="half header-button save" href="javascript:void(0);"><?= t('Speichern') ?></a>
                <a class="half header-button cancel" href="javascript:void(0);"><?= t('Abbrechen') ?></a>
            </div>
        </div>
    </div>



    <script>
        window.addEventListener('spaConfigDone', function() {
            if (window.spa && window.spa.AcceptCookies) {
                window.spa.AcceptCookiesInstance<?= $bID ?> = new window.spa.AcceptCookies();
                window.spa.AcceptCookiesInstance<?= $bID ?>.configModule({});
            }
        });
        window.addEventListener('spaInitDone', function() {
            if (window.spa && window.spa.AcceptCookies) {
                window.spa.AcceptCookiesInstance<?= $bID ?>.initModule(document.getElementById('accept-cookies-wrapper-<?= $bID ?>'));
            }
        });
    </script>
<?php  } ?>
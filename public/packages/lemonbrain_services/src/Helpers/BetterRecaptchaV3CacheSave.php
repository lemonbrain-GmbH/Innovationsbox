<?php

declare(strict_types=1);

namespace Helpers;

use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Http\ResponseAssetGroup;
use Concrete\Core\Support\Facade\Application as App;

/**
 * For cache save use:
 *  - use the requireAsset method in the block controller's registerViewAssets method
 *  - use only the showInput method in the block view
 */

class BetterRecaptchaV3CacheSave
{
    public function __construct()
    {
        $al = AssetList::getInstance();
        $al->register('javascript', 'recaptcha_render_vanilla', 'js/min/recaptchaV3-vanilla-js.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], 'lemonbrain_services');
        $al->registerGroup(
            'recaptcha_v3_vanilla',
            [
                ['javascript', 'recaptcha_render_vanilla'],
                ['javascript', 'recaptcha_api'],
            ]
        );
    }

    public function requireAsset()
    {
        $responseAssets = ResponseAssetGroup::get();
        $responseAssets->requireAsset('recaptcha_v3_vanilla');
    }
    public function showInput()
    {
        $config = App::getFacadeApplication()->app->make('config');
        echo '<div id="' . uniqid('hwh') . '" class="grecaptcha-box recaptcha-v3" data-sitekey="' . h($config->get('captcha.recaptcha_v3.site_key')) . '" data-badge="' . h($config->get('captcha.recaptcha_v3.position')) . '"></div>';
    }
}

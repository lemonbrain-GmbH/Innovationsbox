<?php

declare(strict_types=1);

namespace Helpers;

use Concrete\Core\Http\ResponseAssetGroup;
use Concrete\Core\Support\Facade\Application as App;

/**
 * @deprecated use BetterRecaptchaV3CacheSave instead
 * when block view is cached this code is not executed and therefore the asset does not get loaded
 */
class BetterRecaptchaV3
{
    public function showInput()
    {
        $config = App::getFacadeApplication()->app->make('config');

        $responseAssets = ResponseAssetGroup::get();
        $responseAssets->requireAsset('recaptcha_v3');

        echo '<div id="' . uniqid('hwh') . '" class="grecaptcha-box recaptcha-v3" data-sitekey="' . h($config->get('captcha.recaptcha_v3.site_key')) . '" data-badge="' . h($config->get('captcha.recaptcha_v3.position')) . '"></div>';
    }
}

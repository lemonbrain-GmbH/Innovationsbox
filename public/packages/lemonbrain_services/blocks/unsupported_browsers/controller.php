<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainServices\Block\UnsupportedBrowsers;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;

class Controller extends BlockController
{
    public function __construct($obj = null)
    {
        parent::__construct($obj, realpath(dirname(__FILE__)));
    }

    public function registerViewAssets($outputContent = '')
    {
        // the loading of the lb asset (window.spa object) is not done here
        // so that it can be loaded in the main package (eg. page_theme.php)
        // depending if lb or spa (legacy) asset is used
        // $this->requireAsset('javascript', 'lb');
        $this->requireAsset('javascript', 'unsupported_browsers');
    }
}

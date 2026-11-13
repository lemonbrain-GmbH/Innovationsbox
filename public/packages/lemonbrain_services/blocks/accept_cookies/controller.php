<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainServices\Block\AcceptCookies;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;

class Controller extends BlockController
{
    public function __construct($obj = null)
    {
        parent::__construct($obj, realpath(dirname(__FILE__)));
    }
}

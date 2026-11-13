<?php

declare(strict_types=1);

/* @namespace */

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;

defined('C5_EXECUTE') or die('Access Denied.');

class Controller extends BlockController
{
    public function __construct($obj = null)
    {
        parent::__construct($obj, realpath(dirname(__FILE__)));
    }
}

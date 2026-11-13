<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');
echo method_exists($this->controller, 'renderForm') ? $this->controller->renderForm($view) : t('You need to have %s installed, in order to get a form to show up here.', sprintf('<a href="%s" target="_blank">%s</a>', 'http://www.concrete5.org/marketplace/addons/block-developer', t('Block Developer')));

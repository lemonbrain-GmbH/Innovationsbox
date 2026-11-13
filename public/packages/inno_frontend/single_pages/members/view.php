<?php

declare(strict_types=1);
defined('C5_EXECUTE') or die('Access Denied.');
$response = Redirect::to('/members/directory');
$response->send();
exit;

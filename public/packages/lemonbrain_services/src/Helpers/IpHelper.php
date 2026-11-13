<?php

declare(strict_types=1);

namespace Helpers;

defined('C5_EXECUTE') or die('Access Denied.');

class IpHelper
{
    public function getClientIP()
    {
        $ip = '';
        if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        $ipSplit = explode(':', $ip);
        if (count($ipSplit) > 1) {
            $ip = $ipSplit[0];
        }

        return $ip;
    }
}

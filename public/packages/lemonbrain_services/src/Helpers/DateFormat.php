<?php

declare(strict_types=1);

namespace Helpers;

use IntlDateFormatter;

defined('C5_EXECUTE') or die('Access Denied.');

class DateFormat
{
    private $locale = 'de_CH';
    private $pattern = null;

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    public function localeFormat($locale, $date, $pattern = null)
    {
        $this->setLocale($locale);
        return $this->format($date, $pattern);
    }

    public function format($date, $pattern = null)
    {
        $formatter = new IntlDateFormatter($this->locale, IntlDateFormatter::FULL, IntlDateFormatter::FULL);
        $pattern = $pattern ?? $this->pattern;
        if ($pattern) {
            $formatter->setPattern($pattern ?? $this->pattern);
        }
        return $formatter->format($date);
    }
}

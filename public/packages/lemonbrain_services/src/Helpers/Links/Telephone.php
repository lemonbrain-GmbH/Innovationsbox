<?php

declare(strict_types=1);

namespace Helpers\Links;

use Concrete\Core\Support\Facade\Application as App;
use Concrete\Core\User\User;

class Telephone implements ILink
{
    public const CH_START = '00';
    public const CH = '41';
    public const PHONE_NUMBER_LENGTH = 13;
    public const ZERO = '0';
    public const SPACE = ' ';
    public const PLUS = '+';

    /**
     * Creates a HTML anchor tag from a swiss telephone number address
     *
     * @param string $str          The telephone number
     * @param string $title        Title attribute of tag
     * @param string $innerText    Text representation of the anchor tag
     * @param string $linkClasses  Additional classes on the tag
     * @param string $metaPropName Add a meta tag with this property name
     **/
    public function transformToLink(
        string $str,
        string $title = '',
        string $innerText = '',
        string $linkClasses = '',
        string $metaPropName = '',
    ): string {
        $u = App::make(User::class);
        $metaTag = null;
        $anchorString = '';
        $errorString = '';
        $str = trim($str);
        $numberString = str_replace(' ', '', $str);

        if ($metaPropName) {
            $metaTag = '<meta itemprop="' . $metaPropName . '" content="' . $str . '">';
        }

        try {
            $telephoneLink = $this->formatTelephone($numberString);
        } catch (\Exception $e) {
            if ($u->isRegistered()) {
                $errorString = '<div style="color: #842029; background-color: #f8d7da; border-color: #f5c2c7; padding: 1rem 1rem;">' . $e->getMessage() . '</div>';
            } else {
                $anchorString = "<a href='tel:{$str}' title='{$title}' class='{$linkClasses}'><span style='white-space: nowrap;'>{$str}</span>{$metaTag}</a>";
            }
        }

        if (isset($telephoneLink)) {
            try {
                $formattedStr = $innerText ? $innerText : $this->getTextFormat($str, $numberString, $telephoneLink);
            } catch (\Exception $e) {
                if ($u->isRegistered()) {
                    $errorString = '<div style="color: #842029; background-color: #f8d7da; border-color: #f5c2c7; padding: 1rem 1rem;">' . $e->getMessage() . '</div>';
                } else {
                    $anchorString = "<a href='tel:{$str}' title='{$title}' class='{$linkClasses}'><span style='white-space: nowrap;'>{$str}</span>{$metaTag}</a>";
                }
            }

            if (isset($formattedStr) && trim($formattedStr)) {
                $anchorString = "<a href='tel:{$telephoneLink}' title='{$title}' class='{$linkClasses}'><span style='white-space: nowrap;'>{$formattedStr}</span>{$metaTag}</a>";
            }
        }
        return $errorString . ' ' . $anchorString;
    }

    /**
     * This function is just working when we only expect swiss numbers
     **/
    public function formatTelephone(
        string $numberString,
        bool $useZeros = true,
        bool $removeWhitespace = false
    ): string {
        if ($numberString[0] === '0' && $numberString[1] === '0' && strlen($numberString) === self::PHONE_NUMBER_LENGTH) {
            return $numberString;
        }

        $start = '';
        if ($useZeros) {
            $start = self::CH_START;
        }
        if ($removeWhitespace) {
            $numberString = trim($numberString);
            $numberString = str_replace(' ', '', $numberString);
        }
        $telephoneLink = '';
        if ($numberString[0] === '0' && $numberString[1] !== '0') {
            $telephoneLink = $start . self::CH . substr($numberString, 1);
        }
        if ($numberString[0] === '+') {
            $telephoneLink = $start . substr($numberString, 1);
        }
        if ($numberString[0] === '0' && $numberString[1] === '0') {
            $telephoneLink = $numberString;
        }

        if ($numberString[0] !== '+' && $numberString[0] !== '0') {
            throw new \Exception(t('Die Telefonnummer sollte mit einer 0 oder + beginnen.'));
        }

        return $telephoneLink;
    }

    private function getTextFormat(
        string $str,
        string $numberString,
        string $telephoneLink
    ): string {
        $add_space_btw = false;
        if (strlen(trim($str)) === strlen($numberString)) {
            $add_space_btw = true;
        }

        $hasZerosAndCountryCode =
            $numberString[0] === '0' && $numberString[1] === '0' &&
            $numberString[2] === '4' && $numberString[3] === '1';
        $has_plus = $numberString[0] === self::PLUS;

        $arr = str_split($telephoneLink);
        if (count($arr) === self::PHONE_NUMBER_LENGTH && $arr[0] === '0' && $arr[1] === '0') {
            if (false === $add_space_btw) {
                return trim($str);
            } elseif ($hasZerosAndCountryCode) {
                $textFormat =
                    self::ZERO .
                    self::ZERO .
                    self::CH .
                    self::SPACE .
                    $arr[4] .
                    $arr[5] .
                    self::SPACE .
                    $arr[6] .
                    $arr[7] .
                    $arr[8] .
                    self::SPACE .
                    $arr[9] .
                    $arr[10] .
                    self::SPACE .
                    $arr[11] .
                    $arr[12];
            } elseif ($has_plus) {
                $textFormat =
                    self::PLUS .
                    self::CH .
                    self::SPACE .
                    $arr[4] .
                    $arr[5] .
                    self::SPACE .
                    $arr[6] .
                    $arr[7] .
                    $arr[8] .
                    self::SPACE .
                    $arr[9] .
                    $arr[10] .
                    self::SPACE .
                    $arr[11] .
                    $arr[12];
            } else {
                $textFormat =
                    self::ZERO .
                    $arr[4] .
                    $arr[5] .
                    self::SPACE .
                    $arr[6] .
                    $arr[7] .
                    $arr[8] .
                    self::SPACE .
                    $arr[9] .
                    $arr[10] .
                    self::SPACE .
                    $arr[11] .
                    $arr[12];
            }
        } else {
            throw new \Exception(t('Das Telefonnummernformat ist falsch'));
        }

        return $textFormat;
    }
}

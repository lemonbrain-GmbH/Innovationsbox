<?php

declare(strict_types=1);

namespace Helpers\Links;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use Concrete\Core\User\User;

class EmailAddress implements ILink
{
    public const MAILTO = 'mailto:';

    /**
     * Creates a HTML anchor tag from a mail address
     *
     * @param string $str                  The mail address
     * @param string $title                Title attribute of tag
     * @param string $innerText            Text representation of the anchor tag
     * @param string $linkClasses          Additional classes on the tag
     * @param string $metaPropName         Add a meta tag with this property name
     * @param string $additionalHrefParams Like mail subject or body. Use getParameters() to pass an url encoded string.
     **/
    public function transformToLink(
        string $str,
        string $title = '',
        string $innerText = '',
        string $linkClasses = '',
        string $metaPropName = '',
        string $additionalHrefParams = ''
    ): string {
        $u = App::make(User::class);
        $str = trim($str);
        $errorString = '';
        $metaTag = null;

        if ($metaPropName) {
            $metaTag = '<meta itemprop="' . $metaPropName . '" content="' . $str . '">';
        }

        $formattedStr = $innerText ? $innerText : $str;

        if ($u->isRegistered() && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
            $errorString = '<div style="color: #842029; background-color: #f8d7da; border-color: #f5c2c7; padding: 1rem 1rem;">Mailaddress is not valid</div>';
        }

        if (isset($formattedStr) && trim($formattedStr)) {
            $anchorString = "<a href='{self::MAILTO}:{$str}{$additionalHrefParams}' title='{$title}' class='{$linkClasses}'><span style='white-space: nowrap;'>{$formattedStr}</span>{$metaTag}</a>";
        }
        return $errorString . ' ' . $anchorString;
    }

    /**
     * Use this function to generate $additionalHrefParams
     **/
    public function getParameters(string $subject, string $body): string
    {
        $purified_subject = rawurlencode(
            App::make(HTML::class)
            ->allow_only($subject, [])
        );
        $purified_body = rawurlencode(
            App::make(HTML::class)
            ->allow_only($body, [])
        );
        return "?subject=$purified_subject&body=$purified_body";
    }
}

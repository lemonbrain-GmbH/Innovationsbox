<?php

declare(strict_types=1);

namespace Helpers\Links;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Application as App;
use Concrete\Core\User\User;

class EmailAddressObfuscated extends EmailAddress implements ILink
{
    /**
     * Replaces all mail addresses found with HTML anchor tags.
     *
     * @param string $str         An arbitrary string in which you want simple email addresses replaced with an anchor tag
     * @param string $innerText   Text representation of the anchor tag
     * @param string $linkClasses Additional classes on the tag
     **/
    public function findAndReplaceWithLinks(
        string $str,
        string $innerText = '',
        string $linkClasses = '',
    ): string {
        $pattern = '/[._\-A-Za-zäöüÄÖÜ]+@[^>\s]+\.[A-Za-zäöüÄÖÜ]{2,6}/u';
        $matches = [];

        preg_match_all($pattern, $str, $matches);
        $matches[0] = array_unique($matches[0]);

        foreach ($matches[0] as $email) {
            $replacement = $this->transformToLink(
                str: $email,
                innerText: $innerText,
                linkClasses: $linkClasses,
            );
            $str = str_replace($email, $replacement, $str);
        }
        return $str;
    }

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

        if (!strpos($str, self::MAILTO)) {
            $str = str_replace(['&auml;', '&ouml;', '&uuml;'], ['ä', 'ö', 'ü'], $str);
            $str = str_replace(['&Auml;', '&Ouml;', '&Uuml;'], ['Ä', 'Ö', 'Ü'], $str);

            $firstCharacter = $str[0];
            $decodedEmail = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
            $email = substr($decodedEmail, 1);
            if (preg_match('/[a-z0-9._-äöüÄÖÜ]/', $firstCharacter)) {
                $email = $firstCharacter . $email;
            }
            $obfuscated = $this->getObfuscatedForJS($email);
            $unique_identifier = App::make('helper/validation/identifier')->getString(18);
            return '<a
                    href=""
                    class="mail-' . $unique_identifier . '"
                    title="' . $title . '"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-mail-unveil="true"
                    data-mail-obfuscated="' . $obfuscated . '"
                    data-mail-unique-id="' . $unique_identifier . '"
                    data-mail-title="' . $title . '"
                    data-mail-inner-text="' . $innerText . '"
                    data-mail-link-classes="' . $linkClasses . '"
                    data-mail-additional-href-params="' . $additionalHrefParams . '"
                    data-mail-meta-prop-name="' . $metaPropName . '"
                >'
                . $innerText .
            '</a>';
        }
        if ($u->isRegistered()) {
            $str = '<div style="color: #842029; background-color: #f8d7da; border-color: #f5c2c7; padding: 1rem 1rem;">' . t('Spam possibility: Enter the mailaddress in plaintext, the link will be created automatically. Or use it with care.') . '</div>' . $str;
        }
        return $str;
    }

    private function getObfuscatedForJS(string $email): string
    {
        return $this->strRot47($email);
    }

    private function strRot47(string $str): string
    {
        return strtr(
            $str,
            '!#$%&\'()*+,-./0123456789:;=?@ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~',
            'PQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~!#$%&\'()*+,-./0123456789:;=?@ABCDEFGHIJKLMNO'
        );
    }
}

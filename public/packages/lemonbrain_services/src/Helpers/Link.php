<?php

declare(strict_types=1);

// This one is a helper for block developer lemonbrain links

namespace Helpers;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application as App;
use Helpers\Constants\BlockDeveloper as BDConstants;
use Package;
use Permissions;

class LinkAttributes
{
    public const HREF = 'href';
    public const TARGET = 'target';
    public const REL = 'rel';
    public const TITLE = 'title';
    public const SUBJECT = 'subject';
    public const TARGET_SELF = '_self';
    public const TARGET_BLANK = '_blank';
    public const REL_NOOPENER = 'noopener noreferrer';
    public const HASHBANG_SUFFIX = '#!';
}

class LinkTypes
{
    public const TYPE_INTERN = 'intern';
    public const TYPE_EXTERN = 'extern';
    public const TYPE_FILE = 'file';
    public const TYPE_CONTACT = 'contact';
    public const WEBSITE = 'Auf der Website';
    public const EXTERNAL = 'Extern';
    public const CONTACT = 'Kontakt';
    public const FILE = 'Datei';
    public const NOT_FOUND = 'type not found';
}

class Link
{
    /*
    The getLink() function returns an array. Which can then be used in your view template, for example in the following way:
    <?php echo "<a href='{$linkArray['href']}' target='{$linkArray['target']}' title='{$linkArray['title']}' rel='{$linkArray['rel']}'>{$linkArray['title']}</a>" ?>
    */
    /**
     * @param  $link_type
     * @param  $links_link_type_options
     * @param  $link_intern
     * @param  $link_hash_bang
     * @param  $link_extern
     * @param  $link_contact
     * @param  $link_file
     * @param  $link_title
     * @param  $mail_subject
     * @param  $mail_content
     * @return array|string
     */
    public static function getLink($link_type, $links_link_type_options, $link_intern, $link_hash_bang, $link_extern, $link_file, $link_title, $link_contact = '', $mail_subject = '', $mail_content = '')
    {
        $returnArray = [
            LinkAttributes::HREF   => null,
            LinkAttributes::TARGET => LinkAttributes::TARGET_SELF,
            LinkAttributes::REL    => '',
            LinkAttributes::TITLE  => (isset($link_title) && trim($link_title) != '') ? $link_title : null,
        ];

        if (!$links_link_type_options) {
            $links_link_type_options = [
                LinkTypes::TYPE_INTERN  => LinkTypes::WEBSITE,
                LinkTypes::TYPE_EXTERN  => LinkTypes::EXTERNAL,
                LinkTypes::TYPE_FILE    => LinkTypes::FILE,
                LinkTypes::TYPE_CONTACT => LinkTypes::CONTACT,
            ];
        }

        if (trim($link_type) != '' && array_key_exists($link_type, $links_link_type_options)) {
            switch ($link_type) {
                case LinkTypes::TYPE_INTERN:
                    if (isset($link_intern)) {
                        if (!is_object($link_intern)) {
                            $link_intern = Page::getByID($link_intern);
                        }
                        $returnArray[LinkAttributes::HREF] = $link_intern->getCollectionLink();

                        if (!isset($returnArray[LinkAttributes::TITLE])) {
                            $returnArray[LinkAttributes::TITLE] = $link_intern->getCollectionName();
                        }
                    }

                    if (isset($link_hash_bang) && trim($link_hash_bang) != '') {
                        if (preg_match('/^[#?\/]/', $link_hash_bang)) {
                            $link_add = $link_hash_bang;
                        } else {
                            $link_add = LinkAttributes::HASHBANG_SUFFIX . $link_hash_bang;
                        }
                        if (!isset($returnArray[LinkAttributes::HREF])) {
                            $returnArray[LinkAttributes::HREF] = $link_add;
                        } else {
                            $returnArray[LinkAttributes::HREF] .= $link_add;
                        }
                    }
                    return $returnArray;
                    break;
                case LinkTypes::TYPE_EXTERN:
                    if (isset($link_extern) && trim($link_extern) != '') {
                        $returnArray[LinkAttributes::HREF] = $link_extern;

                        if (!isset($returnArray[LinkAttributes::TITLE])) {
                            $returnArray[LinkAttributes::TITLE] = $link_extern;
                        }
                    }

                    $returnArray[LinkAttributes::TARGET] = LinkAttributes::TARGET_BLANK;
                    $returnArray[LinkAttributes::REL] = LinkAttributes::REL_NOOPENER;
                    break;
                case LinkTypes::TYPE_FILE:
                    if (is_object($link_file) && ($_fp = new Permissions($link_file)) && $_fp->canViewFile()) {
                        $returnArray[LinkAttributes::HREF] = $link_file->getRelativePath();

                        if (!isset($returnArray[LinkAttributes::TITLE])) {
                            $returnArray[LinkAttributes::TITLE] = $link_file->getTitle();
                            $returnArray[LinkAttributes::REL] = LinkAttributes::REL_NOOPENER;
                        }
                    }

                    $returnArray[LinkAttributes::TARGET] = LinkAttributes::TARGET_BLANK;
                    break;
                case LinkTypes::TYPE_CONTACT:
                    if (isset($link_contact) && trim($link_contact) != '') {
                        if (str_contains($link_contact, '@')) {
                            $returnArray[LinkAttributes::HREF] = "mailto:{$link_contact}";
                            if (isset($mail_subject) && trim($mail_subject) != '') {
                                $mail_subject = rawurlencode(
                                    App::make(\Helpers\HTML::class)
                                    ->allow_only($mail_subject, [])
                                );
                            }
                            if (isset($mail_content) && trim($mail_content) != '') {
                                $mail_content = rawurlencode(
                                    App::make(\Helpers\HTML::class)
                                    ->allow_only($mail_content, [])
                                );
                            }
                            $returnArray[LinkAttributes::HREF] = "mailto:$link_contact?subject=$mail_subject&body=$mail_content";
                        } else {
                            $telephone_link = App::getFacadeApplication()->make(\Helpers\Links\Telephone::class)->formatTelephone($link_contact);
                            $returnArray[LinkAttributes::HREF] = "tel:{$telephone_link}";
                        }

                        if (!isset($returnArray[LinkAttributes::TITLE])) {
                            $returnArray[LinkAttributes::TITLE] = $link_extern;
                        }
                    }
                    $returnArray[LinkAttributes::TARGET] = LinkAttributes::TARGET_BLANK;
                    $returnArray[LinkAttributes::REL] = LinkAttributes::REL_NOOPENER;
                    break;
                default:
                    return LinkTypes::NOT_FOUND;
            }
        }

        return $returnArray;
    }


    /**
     * @param $prefixSlug = unique handler in block needs to match with the get config prefix
     * @param $parentSlug = set it to empty string if top level links or give it the name of the repeatable handler
     */
    public static function getForms($parentSlug = 'links', $prefixSlug = '')
    {
        $pkg = Package::getByHandle('lemonbrain_services');
        return str_replace('%parent%', $parentSlug, str_replace('%prefix%', $prefixSlug, file_get_contents($pkg->getPackagePath() . '/templates/LinkTemplate.php')));
    }

    /*
    Every config/link doesnt matter if in repeatable or not needs a different prefix
    */
    /**
     * @param $prefixSlug  = unique handler in block
     * @param $prefixLabel = Text label in front of the subfield names
     * @param $required
     */
    public static function getConfig($prefixSlug = '', $prefixLabel = '', $required = true, $tab = '')
    {
        return [
            $prefixSlug . BDConstants::LINK_TYPE      => [
                BDConstants::FIELD_TYPE       => BDConstants::FIELD_TYPE_SELECT,
                BDConstants::FIELD_LABEL      => $prefixLabel . t(BDConstants::LABEL_SELECT),
                BDConstants::FIELD_REQUIRED   => $required,
                BDConstants::FIELD_SEARCHABLE => false,
                BDConstants::FIELD_CONFIG     => [
                    BDConstants::FIELD_CONFIG_OPTIONS => [
                        LinkTypes::TYPE_INTERN => t(LinkTypes::WEBSITE),
                        LinkTypes::TYPE_EXTERN => t(LinkTypes::EXTERNAL),
                        LinkTypes::TYPE_FILE   => t(LinkTypes::FILE),
                        LinkTypes::TYPE_CONTACT => LinkTypes::CONTACT,
                    ],
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_INTERN    => [
                BDConstants::FIELD_TYPE       => BDConstants::FIELD_TYPE_PAGE,
                BDConstants::FIELD_LABEL      => $prefixLabel . t(BDConstants::LABEL_PAGE),
                BDConstants::FIELD_REQUIRED   => false,
                BDConstants::FIELD_SEARCHABLE => false,
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_HASH_BANG => [
                BDConstants::FIELD_TYPE        => BDConstants::FIELD_TYPE_HASH_BANG,
                BDConstants::FIELD_LABEL       => $prefixLabel . t(BDConstants::LABEL_HASH_BANG),
                BDConstants::FIELD_DESCRIPTION => t(BDConstants::DESCRIPTION_HASH_BANG),
                BDConstants::FIELD_REQUIRED    => false,
                BDConstants::FIELD_SEARCHABLE  => false,
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_EXTERN    => [
                BDConstants::FIELD_TYPE        => BDConstants::FIELD_TYPE_URL,
                BDConstants::FIELD_LABEL       => $prefixLabel . t(BDConstants::LABEL_URL),
                BDConstants::FIELD_DESCRIPTION => t(BDConstants::DESCRIPTION_URL),
                BDConstants::FIELD_REQUIRED    => false,
                BDConstants::FIELD_SEARCHABLE  => false,
                BDConstants::FIELD_VIEW        => [
                    BDConstants::FIELD_TYPE_VIEW_NEW_WINDOW => true,
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_CONTACT    => [
                BDConstants::FIELD_TYPE        => BDConstants::FIELD_TYPE_TEXT,
                BDConstants::FIELD_LABEL       => $prefixLabel . t(BDConstants::LABEL_LINK),
                BDConstants::FIELD_DESCRIPTION => t(BDConstants::DESCRIPTION_LINK),
                BDConstants::FIELD_REQUIRED    => false,
                BDConstants::FIELD_SEARCHABLE  => true,
                BDConstants::FIELD_VIEW        => [
                    BDConstants::FIELD_TYPE_VIEW_NEW_WINDOW => true,
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_FILE      => [
                BDConstants::FIELD_TYPE       => BDConstants::FIELD_TYPE_FILE,
                BDConstants::FIELD_LABEL      => $prefixLabel . t(BDConstants::LABEL_FILE),
                BDConstants::FIELD_REQUIRED   => false,
                BDConstants::FIELD_SEARCHABLE => false,
                BDConstants::FIELD_VIEW       => [
                    LinkAttributes::TARGET => LinkAttributes::TARGET_BLANK,
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_TITLE     => [
                BDConstants::FIELD_TYPE        => BDConstants::FIELD_TYPE_TEXT,
                BDConstants::FIELD_LABEL       => $prefixLabel . t(BDConstants::LABEL_TEXT),
                BDConstants::FIELD_DESCRIPTION => t(BDConstants::DESCRIPTION_TEXT),
                BDConstants::FIELD_REQUIRED    => false,
                BDConstants::FIELD_SEARCHABLE  => true,
                BDConstants::FIELD_VIEW        => [
                    BDConstants::FIELD_TYPE_VIEW_SKIP_H => true,
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_SUBJECT    => [
                BDConstants::FIELD_TYPE        => BDConstants::FIELD_TYPE_TEXT,
                BDConstants::FIELD_LABEL       => $prefixLabel . t(BDConstants::LABEL_SUBJECT),
                BDConstants::FIELD_DESCRIPTION => t(BDConstants::DESCRIPTION_SUBJECT),
                BDConstants::FIELD_REQUIRED    => false,
                BDConstants::FIELD_SEARCHABLE  => true,
                BDConstants::FIELD_VIEW        => [
                    BDConstants::FIELD_TYPE_VIEW_NEW_WINDOW => true,
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
            $prefixSlug . BDConstants::LINK_CONTENT    => [
                BDConstants::FIELD_TYPE        => BDConstants::FIELD_TYPE_TEXTAREA,
                BDConstants::FIELD_LABEL       => $prefixLabel . t(BDConstants::LABEL_CONTENT),
                BDConstants::FIELD_DESCRIPTION => t(BDConstants::DESCRIPTION_CONTENT),
                BDConstants::FIELD_REQUIRED    => false,
                BDConstants::FIELD_SEARCHABLE  => false,
                BDConstants::FIELD_VIEW        => [
                    BDConstants::FIELD_TYPE_VIEW_NEW_WINDOW => true,
                ],
                BDConstants::FIELD_TAB      => $tab,
            ],
        ];
    }
}

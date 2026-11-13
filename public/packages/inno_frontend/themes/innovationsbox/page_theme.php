<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Theme\Innovationsbox;

use Concrete\Core\Page\Theme\Theme;

class PageTheme extends Theme
{
    public function __construct()
    {
        parent::__construct();
        if (!defined('DIR_COMPONENTS')) {
            define('DIR_COMPONENTS', DIR_BASE . '/packages/inno_frontend/components');
        }
    }

    public function getThemeName()
    {
        return 'Innovationsbox - Theme';
    }

    public function getThemeDescription()
    {
        return 'Theme for new corporate Innovationsbox website';
    }

    public function registerAssets()
    {
        $this->requireAsset('inno_frontend');
    }

    public function getThemeDefaultBlockTemplates()
    {
        return [
            'content'  => 'innovationsbox',
            'autonav'  => 'innovationsbox',
            'page_list'  => 'innovationsbox',
            'feature_link'  => 'innovationsbox',
            'image'  => 'webp',
            // "unsupported_browsers"  => "innovationsbox",
            // "accept_cookies"  => "innovationsbox",
        ];
    }

    public function getThemeEditorClasses()
    {
        return [
            ['title' => t('Zitat/Akzent'), 'menuClass' => 'accent', 'spanClass' => 'accent'],
            ['title' => t('Frage (FAQ)'), 'menuClass' => 'question', 'spanClass' => 'question'],
            ['title' => t('Button (Akzent)'), 'element' => 'a', 'attributes' => ['class' => 'btn']],
            ['title' => t('Button (Dunkel)'), 'element' => 'a', 'attributes' => ['class' => 'btn btn--dark']],
            // array('title' => t('Small'), 'menuClass' => 'small', 'spanClass' => 'small'),
            // array('title' => t('Bold'), 'menuClass' => 'bold', 'spanClass' => 'bold'),
            // array('title' => t('Überschrift 1 Stil'), 'menuClass' => 'h1-style', 'spanClass' => 'h1-style'),
            // array('title' => t('Überschrift 2 Stil'), 'menuClass' => 'h2-style', 'spanClass' => 'h2-style'),
            // array('title' => t('Überschrift 3 Stil'), 'menuClass' => 'h3-style', 'spanClass' => 'h3-style'),
            // array('title' => t('Überschrift 4 Stil'), 'menuClass' => 'h4-style', 'spanClass' => 'h4-style'),
            // array('title' => t('Überschrift 5 Stil'), 'menuClass' => 'h5-style', 'spanClass' => 'h5-style'),
            // array('title' => t('Überschrift 6 Stil'), 'menuClass' => 'h6-style', 'spanClass' => 'h6-style'),
        ];
    }
}

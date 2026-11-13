<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Attribute\Key\SiteKey as SiteAttributeKey;
use Concrete\Core\Attribute\Key\UserKey as UserAttributeKey;
use Concrete\Core\Attribute\Type as AttributeType;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Package\Package;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Page\Theme\Theme as PageTheme;
use Concrete\Core\Routing\RouterInterface;
use Concrete\Core\Support\Facade\Application as App;
use Concrete\Core\User\Group\Group;

class Controller extends Package
{
    protected $pkgHandle = 'inno_frontend';
    protected $appVersionRequired = '9.5.0';
    protected $pkgVersion = '1.2.0';
    protected $pkg;
    protected $db;

    protected $packageDependencies = [];

    public function __construct(\Concrete\Core\Application\Application $app)
    {
        parent::__construct($app);

        // External dependencies are only enforced outside test environments.
        // In test (Docker E2E), these packages are unavailable but not actually
        // used by any code — they're infrastructure/tooling packages.
        if (getenv('CONCRETE5_ENV') !== 'test') {
            $this->packageDependencies = [
                'block_developer' => '1.4.4',
                'lemonbrain_services' => '5.0.13',
            ];
        }
    }
    protected $pkgAutoloaderRegistries = [
        'src/Service' => '\Concrete\Package\InnoFrontend\Service',
    ];

    public function getPackageDescription()
    {
        return t('Frontend theme and UI components for Innovationsbox.');
    }

    public function getPackageName()
    {
        return t('Innovationsbox – Frontend');
    }

    /**
    * Initialize the instance.
    *
    * @param Application $app the application instance
    */
    // public function __construct(Application $app)
    // {
    //     $this->app = $app;
    //     // Classes
    //     $this->app->singleton(\Helpers\DateFormat::class);
    // }

    public function on_start()
    {
        if (!defined('DIR_COMPONENTS')) {
            define('DIR_COMPONENTS', DIR_BASE . '/packages/inno_frontend/components');
        }

        // Force profile completion for logged-in users with missing required attributes
        $app = $this->app;
        $director = $app->make('director');
        $director->addListener('on_page_view', function ($event) use ($app) {
            $u = $app->make(\Concrete\Core\User\User::class);
            if (!$u->isRegistered()) {
                return;
            }

            $page = $event->getPageObject();
            if (!$page || $page->isError() || $page->isAdminArea()) {
                return;
            }

            $ui = \Concrete\Core\User\UserInfo::getByID($u->getUserID());
            if (!$ui) {
                return;
            }

            $checker = new \Concrete\Package\InnoFrontend\Service\ProfileCompletionChecker();
            $path = (string) ($page->getCollectionPath() ?? '');
            if ($checker->needsCompletion($ui, $path)) {
                $editUrl = (string) \Concrete\Core\Support\Facade\Url::to('/account/edit_profile') . '?complete=1';
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('HTTP/1.1 302 Found');
                header('Location: ' . $editUrl);
                exit;
            }
        });

        // Workaround: CMS core auth controller redirects to /login/{handle}/{element}
        // but the correct route is /login/callback/{handle}/{element}.
        // Elements in the auth controller's apiMethods can go through callback.
        // Elements NOT in apiMethods (password_sent, view) must use a query param
        // because callback() rejects them.
        $router = $this->app->make(RouterInterface::class);
        $router->register('/login/concrete/{element}', function ($element) {
            $apiMethods = ['forgot_password', 'v', 'change_password', 'password_changed',
                'email_validated', 'invalid_token', 'required_password_upgrade'];
            if (in_array($element, $apiMethods, true)) {
                return \Redirect::to('/login/callback/concrete/' . $element);
            }
            if ($element === 'view') {
                return \Redirect::to('/login');
            }
            $url = (string) \Concrete\Core\Support\Facade\Url::to('/login') . '?auth_element=' . urlencode($element);
            return new \Concrete\Core\Routing\RedirectResponse($url);
        });
        $router->register('/login/concrete/{element}/{token}', function ($element, $token) {
            return \Redirect::to('/login/callback/concrete/' . $element . '/' . $token);
        });

        // Redirect /projektidee to /projektidee/einreichen
        $router->register('/projektidee', function () {
            return \Redirect::to('/projektidee/einreichen');
        });

        $al = AssetList::getInstance();
        //   $al->register('css', 'accept_cookies', 'css/min/accept_cookies/accept_cookies.min.css', array('position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => true), $this);
        $al->register('javascript', 'inno_frontend.main', 'themes/innovationsbox/js/main.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'inno_frontend.manifest', 'themes/innovationsbox/js/manifest.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'inno_frontend.vendor', 'themes/innovationsbox/js/vendor.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        //
        // If you need a REST API Endpoint use it always beneath /api/* - https://documentation.concretecms.org/9-x/developers/security/route


        $al->registerGroup(
            'inno_frontend',
            [
                ['javascript', 'inno_frontend.manifest'],
                ['javascript', 'inno_frontend.vendor'],
                ['javascript', 'inno_frontend.main'],
            ]
        );

        // Single page assets — innovationen
        $al->register('css', 'inno_frontend.innovationen', 'themes/innovationsbox/css/single_pages/innovationen.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.innovationen', 'themes/innovationsbox/js/single_pages/innovationen.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/innovationen', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.innovationen'],
            ['javascript', 'inno_frontend.innovationen'],
        ]);

        // Single page assets — projektraum
        $al->register('css', 'inno_frontend.projektraum', 'themes/innovationsbox/css/single_pages/projektraum.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.projektraum', 'themes/innovationsbox/js/single_pages/projektraum.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/projektraum', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.projektraum'],
            ['javascript', 'inno_frontend.projektraum'],
        ]);

        // Single page assets — projektidee-einreichen
        $al->register('css', 'inno_frontend.projektidee-einreichen', 'themes/innovationsbox/css/single_pages/projektidee-einreichen.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.projektidee-einreichen', 'themes/innovationsbox/js/single_pages/projektidee-einreichen.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/projektidee-einreichen', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.projektidee-einreichen'],
            ['javascript', 'inno_frontend.projektidee-einreichen'],
        ]);

        // Single page assets — projektidee-meine
        $al->register('css', 'inno_frontend.projektidee-meine', 'themes/innovationsbox/css/single_pages/projektidee-meine.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.projektidee-meine', 'themes/innovationsbox/js/single_pages/projektidee-meine.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/projektidee-meine', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.projektidee-meine'],
            ['javascript', 'inno_frontend.projektidee-meine'],
        ]);

        // Single page assets — moderation
        $al->register('css', 'inno_frontend.moderation', 'themes/innovationsbox/css/single_pages/moderation.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.moderation', 'themes/innovationsbox/js/single_pages/moderation.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/moderation', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.moderation'],
            ['javascript', 'inno_frontend.moderation'],
        ]);

        // Single page assets — moderation-detail
        $al->register('css', 'inno_frontend.moderation-detail', 'themes/innovationsbox/css/single_pages/moderation-detail.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.moderation-detail', 'themes/innovationsbox/js/single_pages/moderation-detail.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/moderation-detail', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.moderation-detail'],
            ['javascript', 'inno_frontend.moderation-detail'],
        ]);

        // Single page assets — expert-anfragen
        $al->register('css', 'inno_frontend.expert-anfragen', 'themes/innovationsbox/css/single_pages/expert-anfragen.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.expert-anfragen', 'themes/innovationsbox/js/single_pages/expert-anfragen.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/expert-anfragen', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.expert-anfragen'],
            ['javascript', 'inno_frontend.expert-anfragen'],
        ]);
        // Single page assets — moderation-kategorien
        $al->register('css', 'inno_frontend.moderation-kategorien', 'themes/innovationsbox/css/single_pages/moderation-kategorien.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.moderation-kategorien', 'themes/innovationsbox/js/single_pages/moderation-kategorien.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/moderation-kategorien', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.moderation-kategorien'],
            ['javascript', 'inno_frontend.moderation-kategorien'],
        ]);

        // Single page assets — moderation-benutzer
        $al->register('css', 'inno_frontend.moderation-benutzer', 'themes/innovationsbox/css/single_pages/moderation-benutzer.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.moderation-benutzer', 'themes/innovationsbox/js/single_pages/moderation-benutzer.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/moderation-benutzer', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.moderation-benutzer'],
            ['javascript', 'inno_frontend.moderation-benutzer'],
        ]);

        // Single page assets — edit-profile
        $al->register('css', 'inno_frontend.edit-profile', 'themes/innovationsbox/css/single_pages/edit-profile.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);
        $al->register('javascript', 'inno_frontend.edit-profile', 'themes/innovationsbox/js/single_pages/edit-profile.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => false], $this);
        $al->registerGroup('inno_frontend/edit-profile', [
            ['javascript', 'inno_frontend.manifest'],
            ['javascript', 'inno_frontend.vendor'],
            ['javascript', 'inno_frontend.main'],
            ['css', 'inno_frontend.edit-profile'],
            ['javascript', 'inno_frontend.edit-profile'],
        ]);
    }

    public function install()
    {
        $this->pkg = parent::install();
        $this->db = $this->app->make(Connection::class);

        $this->activateTheme();
        $this->configureBlocks();
        $this->registerSinglePageOverrides();
        $this->registerThemePaths();
        $this->symlinkAuthViews();

        $this->installUserAttributes();
        $this->installSiteAttributes();
        $this->installGroups();
        $this->installSinglePages();
        // $this->installXML();
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->pkg = App::make(PackageService::class)->getByHandle($this->pkgHandle);
        $this->db = $this->app->make(Connection::class);

        $this->activateTheme();
        $this->configureBlocks();
        $this->registerSinglePageOverrides();
        $this->registerThemePaths();
        $this->symlinkAuthViews();

        $this->installUserAttributes();
        $this->installSiteAttributes();
        $this->installGroups();
        $this->installSinglePages();
        $this->localiseUserAttributes();
        // $this->installXML();
    }

    // private function installXML()
    // {
    //     $this->installContentFile('config/install.xml');
    // }

    private function installSinglePages(): void
    {
        $theme = PageTheme::getByHandle('innovationsbox');

        $pages = [
            '/innovationen' => ['name' => 'Innovationen', 'excludeNav' => false],
            '/projektraum' => ['name' => 'Innovation', 'excludeNav' => true],
            '/projektidee' => ['name' => 'Projektidee einreichen', 'excludeNav' => false],
            '/projektidee/einreichen' => ['name' => 'Projektidee einreichen', 'excludeNav' => true],
            '/projektidee/meine' => ['name' => 'Meine Projektideen', 'excludeNav' => true],
            '/moderation' => ['name' => 'Moderation', 'excludeNav' => true],
            '/moderation/detail' => ['name' => 'Moderation Detail', 'excludeNav' => true],
            '/moderation/kategorien' => ['name' => 'Kategorien verwalten', 'excludeNav' => true],
            '/moderation/benutzer' => ['name' => 'Benutzer verwalten', 'excludeNav' => true],
            '/expertanfragen' => ['name' => 'Meine Anfragen', 'excludeNav' => true],
        ];

        foreach ($pages as $path => $opts) {
            $page = Page::getByPath($path);
            if (!$page || $page->isError()) {
                SinglePage::add($path, $this->pkg);
                $page = Page::getByPath($path);
            }
            if ($page && !$page->isError()) {
                $page->update(['cName' => $opts['name']]);
                if (is_object($theme)) {
                    $page->setTheme($theme);
                }
                $page->setAttribute('exclude_nav', $opts['excludeNav']);
            }
        }
    }

    private function registerSinglePageOverrides(): void
    {
        $pages = [
            '/login',
            '/register',
            '/account',
            '/account/edit_profile',
            '/account/avatar',
            '/members',
            '/members/directory',
            '/members/profile',
        ];

        foreach ($pages as $path) {
            $page = Page::getByPath($path);
            if ($page && !$page->isError()) {
                $page->update(['pkgID' => $this->getPackageEntity()->getPackageID()]);
            }
        }
    }

    private function activateTheme()
    {
        if (!is_object(PageTheme::getByHandle('innovationsbox'))) {
            PageTheme::add('innovationsbox', $this->pkg);
        }
    }

    private function configureBlocks()
    {
        $blockTypeSet = BlockTypeSet::getByHandle('inno_frontend');
        if (!is_object($blockTypeSet)) {
            BlockTypeSet::add('inno_frontend', 'InnoFrontend', $this->pkg);
        }

        $blocktypes = [
            'innovation_teaser',
            'update_teaser',
            'info_panel',
        ];

        foreach ($blocktypes as $blocktype) {
            $block = BlockType::getByHandle($blocktype);
            if (!is_object($block)) {
                BlockType::installBlockTypeFromPackage($blocktype, $this->pkg);
            }
        }
    }

    private function registerThemePaths(): void
    {
        $config = $this->app->make('config');
        $config->save('app.theme_paths', [
            '/dashboard' => 'dashboard',
            '/dashboard/*' => 'dashboard',
            '/frontend/install' => VIEW_CORE_THEME,
            '/login' => 'innovationsbox',
            '/oauth/authorize' => VIEW_CORE_THEME,
            '/register' => 'innovationsbox',
            '/account' => 'innovationsbox',
            '/account/*' => 'innovationsbox',
            '/members' => 'innovationsbox',
            '/members/*' => 'innovationsbox',
            '/frontend/maintenance_mode' => VIEW_CORE_THEME,
            '/upgrade' => VIEW_CORE_THEME,
        ]);
    }

    private function installUserAttributes(): void
    {
        $textType = AttributeType::getByHandle('text');

        if (!UserAttributeKey::getByHandle('display_name')) {
            UserAttributeKey::add(
                $textType,
                [
                    'akHandle' => 'display_name',
                    'akName' => t('Anzeigename'),
                    'akIsSearchable' => true,
                    'akIsSearchableIndexed' => true,
                    'uakProfileEdit' => true,
                    'uakProfileEditRequired' => true,
                    'uakRegisterEdit' => false,
                    'uakRegisterEditRequired' => false,
                    'uakMemberListDisplay' => true,
                ],
                $this->pkg,
            );
        }

        if (!UserAttributeKey::getByHandle('expertise_area')) {
            UserAttributeKey::add(
                $textType,
                [
                    'akHandle' => 'expertise_area',
                    'akName' => t('Fachgebiet'),
                    'akIsSearchable' => true,
                    'akIsSearchableIndexed' => true,
                    'uakProfileEdit' => true,
                    'uakProfileEditRequired' => false,
                    'uakRegisterEdit' => false,
                    'uakRegisterEditRequired' => false,
                    'uakMemberListDisplay' => true,
                ],
                $this->pkg,
            );
        }
    }

    private function installSiteAttributes(): void
    {
        $textType = AttributeType::getByHandle('text');

        $attrs = [
            'footer_cta_1_label' => t('Footer CTA 1 – Label'),
            'footer_cta_1_link' => t('Footer CTA 1 – Link (URL)'),
            'footer_cta_2_label' => t('Footer CTA 2 – Label'),
            'footer_cta_2_link' => t('Footer CTA 2 – Link (URL)'),
            'footer_legal_1_label' => t('Footer Legal 1 – Label'),
            'footer_legal_1_link' => t('Footer Legal 1 – Link (URL)'),
            'footer_legal_2_label' => t('Footer Legal 2 – Label'),
            'footer_legal_2_link' => t('Footer Legal 2 – Link (URL)'),
        ];

        foreach ($attrs as $handle => $name) {
            if (!SiteAttributeKey::getByHandle($handle)) {
                SiteAttributeKey::add(
                    $textType,
                    [
                        'akHandle' => $handle,
                        'akName' => $name,
                        'akIsSearchable' => false,
                    ],
                    $this->pkg,
                );
            }
        }
    }

    private function symlinkAuthViews(): void
    {
        $link = DIR_BASE . '/application/authentication/concrete';
        $relativeTarget = '../../packages/inno_frontend/authentication/concrete';

        $linkParent = dirname($link);
        if (!is_dir($linkParent)) {
            mkdir($linkParent, 0755, true);
        }

        if (is_link($link)) {
            unlink($link);
        }

        symlink($relativeTarget, $link);
    }

    private function localiseUserAttributes(): void
    {
        $translations = [
            'Birthday' => 'Geburtsdatum',
            'Birthdate' => 'Geburtsdatum',
            'Date of Birth' => 'Geburtsdatum',
            'First Name' => 'Vorname',
            'Last Name' => 'Nachname',
            'Address' => 'Adresse',
            'Phone' => 'Telefon',
            'Gender' => 'Geschlecht',
            'Personal Links' => 'Persönliche Links',
            'Exclude From Nav' => 'Aus Navigation ausschliessen',
            'Exclude From Page List' => 'Aus Seitenliste ausschliessen',
            'Exclude From Search Index' => 'Aus Suchindex ausschliessen',
            'Is Featured' => 'Hervorgehoben',
            'Thumbnail' => 'Vorschaubild',
            'Tags' => 'Schlagwörter',
        ];

        $db = $this->app->make(Connection::class);
        foreach ($translations as $english => $german) {
            $db->executeStatement(
                'UPDATE AttributeKeys SET akName = ? WHERE akName = ?',
                [$german, $english],
            );
        }
    }

    private function installGroups(): void
    {
        if (!Group::getByName('Experte')) {
            Group::add('Experte', t('Users recognised as domain experts for the expert pool.'));
        }

        if (!Group::getByName('Moderator')) {
            Group::add('Moderator', t('Moderators review and approve/reject submitted project ideas.'));
        }
    }
}

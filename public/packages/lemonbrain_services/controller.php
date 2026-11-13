<?php

declare(strict_types=1);

namespace Concrete\Package\LemonbrainServices;

defined('C5_EXECUTE') or die(_('Access Denied.'));

use Concrete\Core\Application\Application;
use Concrete\Core\Asset\Asset;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Block\BlockType\Set as BlockTypeSet;
use Concrete\Core\Command\Task\Manager as TaskManager;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Events\EventDispatcher;
use Concrete\Core\Package\Package;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Routing\RouterInterface;
use LemonbrainServices\Command\Task\Controller\DisplayDebugErrorsController;
use LemonbrainServices\Console\Command\C5BackupBundleCommand;
use LemonbrainServices\Console\Command\C5CopyFilesAndDirectoriesCommand;
use LemonbrainServices\Console\Command\C5CreateAnArchiveCommand;
use LemonbrainServices\Console\Command\C5SqlDumpCommand;
use LemonbrainServices\Console\Command\C5TargetDirectoryCommand;

class Controller extends Package
{
    /**
     * The container instance to be used to build dependencies.
     *
     * @var \Concrete\Core\Application\Application
     */
    protected $app;
    protected $packageDependencies = [
        'block_developer' => '1.2.0',
        'lemonbrain_webp' => '2.0.0',
    ];
    protected $appVersionRequired = '9.1.1';
    protected $pkgVersion = '6.0.4';
    protected $pkgHandle = 'lemonbrain_services';
    protected $pkgName = 'Lemonbrain Services';
    protected $pkgDescription = 'A package for Lemonbrain Services.';
    protected $pkg;
    protected $pkgAutoloaderRegistries = [
        'src/Helpers' => 'Helpers',
        'src'         => 'Devoda\\BlockDeveloper',
        'src/MailOverrider' => 'MailOverrider',
        'src/TaskAutomation' => 'TaskAutomation',
        'src/Backup'  => 'LemonbrainServices\C5Backup',
        'src/Console' => 'LemonbrainServices\Console',
        'src/Command' => 'LemonbrainServices\Command',
    ];
    protected $pkgAutoloaderMapCoreExtensions = true;

    /**
     * Initialize the instance.
     *
     * @param Application $app the application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        // Classes
        $this->app->singleton(\Helpers\DateFormat::class);
        $this->app->singleton(\Helpers\Links\Telephone::class);
        $this->app->singleton(\Helpers\Links\EmailAddress::class);
        $this->app->singleton(\Helpers\Links\EmailAddressObfuscated::class);
        $this->app->singleton(\Helpers\Thumbnails::class);
        $this->app->singleton(\Helpers\BetterRecaptchaV3::class);
        $this->app->singleton(\Helpers\HTML::class);
        $this->app->singleton(\Helpers\SymfonyFileSystemHelper::class);
        $this->app->singleton(\Helpers\IpHelper::class);
        $this->app->singleton(\Helpers\BetterRecaptchaV3CacheSave::class);
        $this->app->singleton(\TaskAutomation\Minuteman::class);
        $this->app->singleton(\TaskAutomation\CronRunningEvent::class);
    }

    public function on_start()
    {
        if ($this->app->isRunThroughCommandLineInterface()) {
            $this->registerCLICommands();
        }


        //deprecated
        $this->app->make(RouterInterface::class)->register('/taskrunner/minuteman', '\TaskAutomation\Minuteman::runScheduler');
        //new - call all minute so cronjob (* * * * *)
        $this->app->make(RouterInterface::class)->register('/taskrunner/minuteman/scheduler', '\TaskAutomation\Minuteman::runScheduler');
        $this->app->make(RouterInterface::class)->register('/taskrunner/minuteman/consumer', '\TaskAutomation\Minuteman::runMessengerConsumer');

        $config = $this->app->make('config');

        // Assets in the theme
        $al = AssetList::getInstance();
        $al->register('javascript', 'jquery-uriAnchor', 'js/min/jquery.uriAnchor-1.1.3.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'jquery-migrate', 'js/min/jquery-migrate-1.4.0.min.js', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'swiped', 'js/min/swiped-events.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'retinajs', 'js/min/retinajs.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'taffy', 'js/min/taffy.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);

        $al->register('javascript', 'spa', 'js/min/spa.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'spa-shell', 'js/min/spa.shell.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);

        $al->register('javascript', 'spa-util', 'js/min/spa.util.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'spa-util-mailAddr', 'js/min/spa.util.mailAddr.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'spa-util-uriAnchor', 'js/min/spa.util.uriAnchor.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'spa-util-cache', 'js/min/spa.util.cache.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'spa-util-functionQueue', 'js/min/spa.util.functionQueue.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);

        $al->register('javascript', 'lb', 'js/min/lb.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-shell', 'js/min/lb.shell.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-util', 'js/min/lb.util.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-util-uriParam', 'js/min/lb.util.uriParam.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-util-uriParam-urlHandler', 'js/min/lb.util.uriParam.urlHandler.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-util-fade', 'js/min/lb.util.fade.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-util-swipe', 'js/min/lb.util.swipe.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'lb-html-sanitizer', 'js/min/htmlSanitizer.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);

        $al->register('javascript', 'popper', 'js/min/popper.1.14.7.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('javascript', 'bootstrap4', 'js/min/bootstrap.4.3.1.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('css', 'bootstrap4', 'css/min/bootstrap.4.3.1.min.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => true], $this);

        $al->registerGroup(
            'bootstrap4',
            [
                ['css', 'bootstrap4'],
                ['javascript', 'popper'],
                ['javascript', 'bootstrap4'],
            ]
        );

        $al->registerGroup(
            'lb-util-uriParam',
            [
                ['javascript', 'lb-util-uriParam'],
                ['javascript', 'lb-util-uriParam-urlHandler'],
            ]
        );

        $al->register('javascript', 'unsupported_browsers', 'js/min/unsupported_browsers/unsupported_browsers.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);

        $al->register('javascript', 'accept_cookies', 'js/min/accept_cookies/accept_cookies.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->register('css', 'accept_cookies', 'css/min/accept_cookies/accept_cookies.min.css', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => true], $this);

        $al->registerGroup(
            'accept_cookies',
            [
                ['css', 'accept_cookies'],
                ['javascript', 'accept_cookies'],
            ]
        );

        // Recaptcha V3 Jquery and Vanilla JS implementation
        $assetUrl = $config->get('captcha.recaptcha_v3.url.javascript_asset');
        $al->register('javascript', 'recaptcha_api', $assetUrl, ['local' => false]);
        $al->register('javascript', 'recaptcha_render', 'js/min/recaptchaV3.min.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], $this);
        $al->registerGroup(
            'recaptcha_v3',
            [
                ['javascript', 'recaptcha_render'],
                ['javascript', 'recaptcha_api'],
            ]
        );

        $al->register('javascript', 'google', 'js/min/google.min.js', ['position' => Asset::ASSET_POSITION_HEADER, 'minify' => false, 'combine' => false], $this);

        $this->app->make(\MailOverrider\ServiceProvider::class)->register();

        $dispatcher = $this->app->make(EventDispatcher::class)->getEventDispatcher();
        $dispatcher->addSubscriber($this->app->make(\TaskAutomation\CronRunningEvent::class));

        $manager = $this->app->make(TaskManager::class);
        $manager->extend(
            DisplayDebugErrorsController::TASK_HANDLE,
            function () {
                return new DisplayDebugErrorsController();
            }
        );
    }

    public function install()
    {
        $this->pkg = parent::install();
        $this->db = $this->app->make(Connection::class);

        $this->configureBlocks();
        $this->addSinglePages();

        $this->installContentFile('tasks.xml');
    }

    public function upgrade()
    {
        $result = parent::upgrade();
        $this->pkg = $this->app->make(PackageService::class)->getByHandle($this->pkgHandle);
        $this->db = $this->app->make(Connection::class);

        $this->configureBlocks();
        $this->addSinglePages();

        $this->installContentFile('tasks.xml');

        return $result;
    }

    public function uninstall()
    {
        $this->pkg = parent::uninstall();
    }

    private function configureBlocks()
    {
        $blockTypeSet = BlockTypeSet::getByHandle('lemonbrain_services', $this->pkg);
        if (!is_object($blockTypeSet)) {
            BlockTypeSet::add('lemonbrain_services', 'Lemonbrain Services', $this->pkg);
        }
    }

    private function addSinglePages()
    {
        $this->addSinglePage('/styleguide', t('Styleguide'), t('Basic Styleguide'), true);
    }

    private function addSinglePage($path, $name, $description, $hidden = false)
    {
        $singlePage = Page::getByPath($path);
        if (!is_object($singlePage) || !intval($singlePage->getCollectionID()) || $singlePage->isError()) {
            $singlePage = SinglePage::add($path, $this->pkg);
        }
        if (is_object($singlePage) && intval($singlePage->getCollectionID())) {
            $singlePage->update(['cName' => $name, 'cDescription' => $description]);
            if ($hidden) {
                $singlePage->setAttribute('exclude_nav', true);
                $singlePage->setAttribute('exclude_sitemapxml', true);
                $singlePage->setAttribute('exclude_page_list', true);
                $singlePage->setAttribute('exclude_search_index', true);
            }
        } else {
            throw new \Exception(t('Error: ' . $path . ' page not created'));
        }
    }

    private function registerCLICommands()
    {
        $commands = [
            new C5BackupBundleCommand(),
            new C5TargetDirectoryCommand(),
            new C5SqlDumpCommand(),
            new C5CopyFilesAndDirectoriesCommand(),
            new C5CreateAnArchiveCommand(),
        ];

        $console = $this->app->make('console');

        if (count($commands) > 0) {
            foreach ($commands as $command) {
                $console->add($command);
            }
        }
    }
}

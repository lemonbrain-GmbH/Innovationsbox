<?php

declare(strict_types=1);

use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;

$pages = ['/members', '/members/directory', '/members/profile'];
foreach ($pages as $path) {
    $p = Page::getByPath($path);
    if (!$p || $p->isError()) {
        SinglePage::add($path);
        echo "Created: $path\n";
    } else {
        echo "Exists: $path\n";
    }
}

// Enable public profiles — required by PublicProfilePageController (returns 404 without this)
$app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
$site = $app->make('site')->getSite();
$config = $site->getConfigRepository();
$config->save('user.profiles_enabled', true);
echo "Public profiles enabled\n";

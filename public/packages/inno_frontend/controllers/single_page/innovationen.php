<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage;

use Concrete\Core\Page\Controller\PageController;

class Innovationen extends PageController
{
    public function view(): void
    {
        $this->requireAsset('inno_frontend/innovationen');
    }
}

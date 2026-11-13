<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage;

use Concrete\Core\Page\Controller\PageController;

class Projektidee extends PageController
{
    public function on_start(): void
    {
        $this->redirect('/projektidee/einreichen');
    }
}

<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Projektidee;

use Concrete\Core\Page\Controller\PageController;

class Einreichen extends PageController
{
    public function view(): void
    {
        $this->requireAsset('inno_frontend/projektidee-einreichen');
        $this->requireAsset('javascript', 'ckeditor');
    }
}

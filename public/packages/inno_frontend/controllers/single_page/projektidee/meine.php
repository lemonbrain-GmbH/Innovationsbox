<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Projektidee;

use Concrete\Core\Page\Controller\PageController;

class Meine extends PageController
{
    public function view(): void
    {
        $this->requireAsset('inno_frontend/projektidee-meine');
        $this->requireAsset('javascript', 'ckeditor');
    }
}

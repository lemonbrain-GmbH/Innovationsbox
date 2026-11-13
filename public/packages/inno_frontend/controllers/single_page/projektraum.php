<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage;

use Concrete\Core\Page\Controller\PageController;

class Projektraum extends PageController
{
    public function view(string $spaceId = ''): void
    {
        $this->set('spaceId', $spaceId);
        $this->requireAsset('inno_frontend/projektraum');

        // Load CKEditor 4 core only (no concrete.js wrapper that requires jQuery)
        $this->requireAsset('javascript', 'ckeditor');
    }
}

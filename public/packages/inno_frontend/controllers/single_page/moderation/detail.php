<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Controller\SinglePage\Moderation;

use Concrete\Core\Page\Controller\PageController;

class Detail extends PageController
{
    public function view(): void
    {
        $this->requireAsset('inno_frontend/moderation-detail');
        $this->requireAsset('javascript', 'ckeditor');
    }
}

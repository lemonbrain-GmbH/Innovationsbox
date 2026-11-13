<?php

declare(strict_types=1);

namespace Application\Block\PageList;

use Concrete\Block\PageList\Controller as CoreController;

class Controller extends CoreController
{
    /**
     * {@inheritdoc}
     */
    public function getRequiredFeatures(): array
    {
        return [
        ];
    }
}

<?php

declare(strict_types=1);

namespace Application\Block\RssDisplayer;

use Concrete\Block\RssDisplayer\Controller as CoreController;

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

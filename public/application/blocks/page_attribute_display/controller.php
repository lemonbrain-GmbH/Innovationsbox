<?php

declare(strict_types=1);

namespace Application\Block\PageAttributeDisplay;

use Concrete\Block\PageAttributeDisplay\Controller as CoreController;

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

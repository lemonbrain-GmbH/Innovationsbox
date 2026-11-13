<?php

declare(strict_types=1);

namespace Application\Block\GoogleMap;

use Concrete\Block\GoogleMap\Controller as CoreController;

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

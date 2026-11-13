<?php

declare(strict_types=1);

namespace Application\Block\CoreStackDisplay;

use Concrete\Block\CoreStackDisplay\Controller as CoreController;

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

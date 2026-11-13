<?php

declare(strict_types=1);

namespace Application\Block\HeroImage;

use Concrete\Block\HeroImage\Controller as CoreController;

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

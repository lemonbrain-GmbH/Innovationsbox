<?php

declare(strict_types=1);

namespace Application\Block\FeatureLink;

use Concrete\Block\FeatureLink\Controller as CoreController;

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

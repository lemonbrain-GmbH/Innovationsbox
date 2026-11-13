<?php

declare(strict_types=1);

namespace Application\Block\Tags;

use Concrete\Block\Tags\Controller as CoreController;

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

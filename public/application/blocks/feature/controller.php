<?php

declare(strict_types=1);

namespace Application\Block\Feature;

use Concrete\Block\Feature\Controller as CoreController;

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

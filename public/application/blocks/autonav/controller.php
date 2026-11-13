<?php

declare(strict_types=1);

namespace Application\Block\Autonav;

use Concrete\Block\Autonav\Controller as CoreController;

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

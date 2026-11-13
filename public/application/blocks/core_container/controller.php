<?php

declare(strict_types=1);

namespace Application\Block\CoreContainer;

use Concrete\Block\CoreContainer\Controller as CoreController;

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

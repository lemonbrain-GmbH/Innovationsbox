<?php

declare(strict_types=1);

namespace Application\Block\EventList;

use Concrete\Block\EventList\Controller as CoreController;

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

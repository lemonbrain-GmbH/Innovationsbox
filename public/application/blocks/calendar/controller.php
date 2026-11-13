<?php

declare(strict_types=1);

namespace Application\Block\Calendar;

use Concrete\Block\Calendar\Controller as CoreController;

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

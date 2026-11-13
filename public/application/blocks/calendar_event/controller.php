<?php

declare(strict_types=1);

namespace Application\Block\CalendarEvent;

use Concrete\Block\CalendarEvent\Controller as CoreController;

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

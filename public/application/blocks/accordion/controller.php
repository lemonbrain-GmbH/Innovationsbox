<?php

declare(strict_types=1);

namespace Application\Block\Accordion;

use Concrete\Block\Accordion\Controller as CoreController;

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

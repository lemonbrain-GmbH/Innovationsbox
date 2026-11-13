<?php

declare(strict_types=1);

namespace Application\Block\Survey;

use Concrete\Block\Survey\Controller as CoreController;

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

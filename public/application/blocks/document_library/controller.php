<?php

declare(strict_types=1);

namespace Application\Block\DocumentLibrary;

use Concrete\Block\DocumentLibrary\Controller as CoreController;

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

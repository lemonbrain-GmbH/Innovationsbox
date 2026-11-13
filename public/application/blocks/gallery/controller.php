<?php

declare(strict_types=1);

namespace Application\Block\Gallery;

use Concrete\Block\Gallery\Controller as CoreController;

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

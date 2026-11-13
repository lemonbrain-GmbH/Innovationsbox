<?php

declare(strict_types=1);

namespace Application\Block\Image;

use Concrete\Block\Image\Controller as CoreController;

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

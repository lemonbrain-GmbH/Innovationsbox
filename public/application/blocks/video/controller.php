<?php

declare(strict_types=1);

namespace Application\Block\Video;

use Concrete\Block\Video\Controller as CoreController;

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

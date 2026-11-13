<?php

declare(strict_types=1);

namespace Application\Block\Content;

use Concrete\Block\Content\Controller as CoreController;

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

<?php

declare(strict_types=1);

namespace Application\Block\CoreAreaLayout;

use Concrete\Block\CoreAreaLayout\Controller as CoreController;

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

<?php

declare(strict_types=1);

namespace Application\Block\TopNavigationBar;

use Concrete\Block\TopNavigationBar\Controller as CoreController;

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

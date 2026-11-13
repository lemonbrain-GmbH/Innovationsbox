<?php

declare(strict_types=1);

namespace Application\Block\PageTitle;

use Concrete\Block\PageTitle\Controller as CoreController;

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

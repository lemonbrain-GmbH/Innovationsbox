<?php

declare(strict_types=1);

namespace Application\Block\Breadcrumbs;

use Concrete\Block\Breadcrumbs\Controller as CoreController;

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

<?php

declare(strict_types=1);

namespace Application\Block\Search;

use Concrete\Block\Search\Controller as CoreController;

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

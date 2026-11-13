<?php

declare(strict_types=1);

namespace Application\Block\ExpressEntryDetail;

use Concrete\Block\ExpressEntryDetail\Controller as CoreController;

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

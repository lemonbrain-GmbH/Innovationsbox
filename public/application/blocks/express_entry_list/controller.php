<?php

declare(strict_types=1);

namespace Application\Block\ExpressEntryList;

use Concrete\Block\ExpressEntryList\Controller as CoreController;

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

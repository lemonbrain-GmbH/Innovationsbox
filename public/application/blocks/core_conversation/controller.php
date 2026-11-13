<?php

declare(strict_types=1);

namespace Application\Block\CoreConversation;

use Concrete\Block\CoreConversation\Controller as CoreController;

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

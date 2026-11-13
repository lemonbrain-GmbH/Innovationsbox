<?php

declare(strict_types=1);

namespace Application\Block\TopicList;

use Concrete\Block\TopicList\Controller as CoreController;

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

<?php

namespace Application\Block\GoogleMap;

use Concrete\Block\GoogleMap\Controller as CoreController;
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

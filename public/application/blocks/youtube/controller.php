<?php

namespace Application\Block\Youtube;

use Concrete\Block\Youtube\Controller as CoreController;
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

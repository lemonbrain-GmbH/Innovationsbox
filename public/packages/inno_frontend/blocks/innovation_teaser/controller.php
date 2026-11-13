<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Block\InnovationTeaser;

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = '';

    protected $btCacheBlockOutput = true;

    protected $btCacheBlockOutputOnPost = true;

    protected $btCacheBlockOutputForRegisteredUsers = false;

    public function getBlockTypeName(): string
    {
        return t('Innovationen-Teaser');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Zeigt die neuesten Innovationen als Kartenraster an.');
    }
}

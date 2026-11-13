<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Block\UpdateTeaser;

use Concrete\Core\Block\BlockController;

class Controller extends BlockController
{
    protected $btTable = '';

    protected $btCacheBlockOutput = true;

    protected $btCacheBlockOutputOnPost = true;

    protected $btCacheBlockOutputForRegisteredUsers = false;

    public function getBlockTypeName(): string
    {
        return t('Innovationsentwicklungen-Teaser');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Zeigt die neuesten Projekt-Updates über alle Innovationen an.');
    }
}

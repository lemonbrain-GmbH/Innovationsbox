<?php

declare(strict_types=1);

namespace Concrete\Package\InnoFrontend\Block\InfoPanel;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\File\File;

class Controller extends BlockController
{
    protected $btTable = 'btInfoPanel';

    protected $btCacheBlockOutput = true;

    protected $btCacheBlockOutputOnPost = true;

    protected $btCacheBlockOutputForRegisteredUsers = false;

    public ?int $fID = null;

    public ?string $content = null;

    public function getBlockTypeName(): string
    {
        return t('Info-Panel');
    }

    public function getBlockTypeDescription(): string
    {
        return t('Bild links, Freitext rechts – für Infoabschnitte wie «Die Innovationsbox».');
    }

    public function view(): void
    {
        $image = null;
        if ($this->fID) {
            $file = File::getByID($this->fID);
            if ($file) {
                $image = $file->getApprovedVersion();
            }
        }

        $this->set('image', $image);
        $this->set('content', LinkAbstractor::translateFrom((string) $this->content));
    }

    public function add(): void
    {
        $this->set('fID', null);
        $this->set('content', '');
    }

    public function edit(): void
    {
        $this->set('fID', $this->fID);
        $this->set('content', LinkAbstractor::translateFromEditMode((string) $this->content));
    }

    public function save($args)
    {
        $args['fID'] = isset($args['fID']) ? (int) $args['fID'] : null;
        $args['content'] = isset($args['content']) ? LinkAbstractor::translateTo((string) $args['content']) : '';

        parent::save($args);
    }
}

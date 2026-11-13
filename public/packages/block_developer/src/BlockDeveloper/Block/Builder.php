<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block;

use Devoda\BlockDeveloper\BlockDeveloper\Block\Builder\Templates;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Builder\View;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Builder\XmlSchema;

class Builder
{
    /**
     * @var BlockController
     */
    protected $blockController;

    /**
     * Builder constructor.
     *
     * @param BlockController $blockController
     */
    public function __construct($blockController)
    {
        $this->blockController = $blockController;
    }

    /**
     * @return bool
     */
    public function XmlSchema()
    {
        return (new XmlSchema($this->blockController))->build();
    }

    /**
     * @return bool
     */
    public function view()
    {
        return (new View($this->blockController))->build();
    }

    /**
     * @return bool
     */
    public function templates()
    {
        return (new Templates($this->blockController))->build();
    }
}

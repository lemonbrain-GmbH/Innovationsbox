<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\StaticCode;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class StaticCode extends FieldType
{
    /**
     * @var bool
     */
    protected $repeatable = true;

    /**
     * @return string
     */
    public function getFieldTypeName()
    {
        return t('Static Code');
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return mixed
     */
    public function buildView($blockController, $field)
    {
        $this->setViewIndent();

        return $this->getViewIndentHTML() . $field->getPrefix() . $this->getViewData('code', null) . $field->getSuffix();
    }
}

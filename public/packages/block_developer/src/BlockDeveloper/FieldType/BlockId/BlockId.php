<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\BlockId;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class BlockId extends FieldType
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
        return t('Block ID');
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

        return $this->getViewIndentHTML() . $field->getPrefix() . '<?php echo $bID; ?>' . $field->getSuffix();
    }
}

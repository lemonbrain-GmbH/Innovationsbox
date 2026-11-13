<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block\Builder;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Generator\XmlSchema as XmlSchemaGenerator;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class XmlSchema
{
    /**
     * @var BlockController
     */
    protected $blockController;

    /**
     * Builder constructor.
     *
     * @param $blockController BlockController
     */
    public function __construct($blockController)
    {
        $this->blockController = $blockController;
    }

    /**
     * @return bool
     */
    public function build()
    {
        if (!method_exists($this->blockController, 'getBlockDeveloperConfig')) {
            return false;
        }

        $databaseTables = [];
        $dbFields = [
            [
                'name' => 'bID',
                'type' => 'I',
                'attributes' => [
                    'key' => true,
                    'unsigned' => true,
                ],
            ],
        ];
        foreach ($this->blockController->getFields() as $fieldSlug => $field) {
            /* @var $field Field */
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            if (($result = $fieldType->getDatabaseTables($this->blockController, $field))
                && is_array($result) && !empty($result)
            ) {
                $databaseTables = array_merge($databaseTables, $result);
            }
            if (($result = $fieldType->getDatabaseFields($this->blockController, $field))
                && is_array($result) && !empty($result)
            ) {
                $dbFields = array_merge($dbFields, $result);
            }
        }
        $databaseTables = array_merge(
            [$this->blockController->getBlockTypeDatabaseTable() => ['fields' => $dbFields]],
            $databaseTables
        );
        $dbXML = $this->blockController->getBlockTypeDirectory() . '/db.xml';

        return (new FileService())->writeFile($dbXML, (new XmlSchemaGenerator())->generate($databaseTables));
    }
}

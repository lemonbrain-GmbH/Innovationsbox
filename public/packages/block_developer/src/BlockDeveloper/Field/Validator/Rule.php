<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;

class Rule
{
    /**
     * @var Field
     */
    protected $field;

    /**
     * @var BlockController
     */
    protected $blockController;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool|int|mixed
     */
    protected $row;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var bool
     */
    protected $repeating = false;

    /**
     * Validation constructor.
     *
     * @param BlockController $blockController
     * @param Field           $field
     * @param int|mixed       $row
     * @param array           $values
     */
    public function __construct($blockController, $field, array $data, $row = false, $values = [])
    {
        $this->blockController = $blockController;
        $this->setField($field);
        $this->setRepeating();
        $this->setData($data);
        $this->row = $row;
        $this->values = $values;
    }

    /**
     * @param Field $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return bool|int|mixed
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return Field
     */
    public function getParentField()
    {
        return $this->getField()->getParent();
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->getField()->getSlug();
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setRepeating()
    {
        $this->repeating = $this->getField()->getFieldTypeIsRepeating();
    }

    /**
     * @return bool
     */
    public function isRepeating()
    {
        return $this->repeating;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}

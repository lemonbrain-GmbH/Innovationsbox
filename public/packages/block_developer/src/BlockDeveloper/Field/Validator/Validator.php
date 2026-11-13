<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;

class Validator
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
    protected $variables = [];

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var mixed
     */
    protected $row;

    /**
     * @var bool
     */
    protected $repeating = false;

    /**
     * Validation constructor.
     *
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function __construct($blockController, $field, array $variables, array $rules)
    {
        $this->blockController = $blockController;
        $this->setField($field);
        $this->setRepeating();
        $this->setRules($rules);
        $this->setData(isset($variables['args']) ? $variables['args'] : []);
        if (isset($variables['row'])) {
            $this->row = $variables['row'];
        }
    }

    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
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
     * @return array
     */
    public function errors()
    {
        return $this->messages();
    }

    /**
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    public function validate()
    {
        foreach ($this->rules as $rule) {
            $exploded = explode(':', $rule);
            $class = '\Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules\\' . $this->_nameSpace($exploded[0]);
            if (class_exists($class)) {
                unset($exploded[0]);
                $values = [];
                if (!empty($exploded)) {
                    $imploded = implode(':', $exploded);
                    $values = explode(',', $imploded);
                }
                $validate = (new $class($this->blockController, $this->getField(), $this->getData(), $this->row, $values))->validate();
                if ($validate !== true) {
                    $this->messages[] = $validate;
                    break 1;
                }
            }
        }

        return $this->messages();
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _nameSpace($name)
    {
        $nameSpaced = implode('', array_map(function ($v, $k) {
            return ucfirst($v);
        }, explode('_', $name), array_keys(explode('_', $name))));

        return $nameSpaced;
    }
}

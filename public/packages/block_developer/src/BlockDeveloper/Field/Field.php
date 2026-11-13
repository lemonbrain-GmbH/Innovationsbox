<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field;

use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Field
{
    /**
     * @var string
     */
    protected $slug;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $tab;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $suffix;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var bool
     */
    protected $searchable = true;

    /**
     * @var FieldType
     */
    protected $fieldType;

    /**
     * @var string
     */
    protected $viewVariable;

    /**
     * @var FieldType
     */
    protected $parent;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var string
     */
    protected $formTemplate = '<div class="form-group">%s%s</div>';

    public function __construct($k, $v, $fieldType)
    {
        $this->setSlug($k);
        $this->setViewVariable('$' . $k);
        $this->setFieldType($fieldType);
        if (isset($v['label'])) {
            $this->setLabel($v['label']);
        }
        if (isset($v['description'])) {
            $this->setDescription($v['description']);
        }
        if (isset($v['tab'])) {
            $this->setTab($v['tab']);
        }
        if (isset($v['view'])) {
            if (isset($v['view']['suffix'])) {
                $this->setSuffix($v['view']['suffix']);
            }
            if (isset($v['view']['prefix'])) {
                $this->setPrefix($v['view']['prefix']);
            }
        }
        if (isset($v['required'])) {
            $this->setRequired($v['required']);
        }
        if (isset($v['searchable'])) {
            $this->setSearchable($v['searchable']);
        }
        if (isset($v['form_template'])) {
            $this->setFormTemplate($v['form_template']);
        }
        $this->setValues($v);
    }

    /**
     * @return string
     */
    public function getFormTemplate()
    {
        return $this->formTemplate;
    }

    /**
     * @param string $formTemplate
     */
    public function setFormTemplate($formTemplate)
    {
        $this->formTemplate = $formTemplate;
    }

    /**
     * @param $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @return FieldType
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getTab()
    {
        return $this->tab;
    }

    /**
     * @param $tab
     */
    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    /**
     * @return mixed
     */
    public function getViewVariable()
    {
        return $this->viewVariable;
    }

    /**
     * @param $viewVariable
     */
    public function setViewVariable($viewVariable)
    {
        $this->viewVariable = $viewVariable;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return FieldType
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param $fieldType
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return mixed
     */
    public function getSuffix()
    {
        return $this->suffix;
    }

    /**
     * @param mixed $suffix
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }

    /**
     * @param bool $searchable
     */
    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
    }

    /**
     * @return bool
     */
    public function getFieldTypeIsRepeating()
    {
        return $this->getFieldType()->isRepeating();
    }

    /**
     * @param       $handle
     * @param       $blockController
     * @param array $variables
     *
     * @return bool
     */
    public function getValidationError($handle, $blockController, $variables = [])
    {
        $class = '\Devoda\BlockDeveloper\BlockDeveloper\Field\Validation\\' . $this->_nameSpace($handle);
        if (class_exists($class)) {
            return (new $class($this, $blockController, $variables))->getError();
        }

        return false;
    }

    /**
     * @param       $type
     * @param       $blockController
     * @param array $variables
     * @param array $miscFields
     *
     * @return string|null
     */
    public function generateFormContent($type, $blockController, $variables = [], $miscFields = [])
    {
        $class = '\Devoda\BlockDeveloper\BlockDeveloper\Field\Form\\' . $this->_nameSpace($type);
        if (class_exists($class)) {
            return (new $class($this, $blockController, $variables, $miscFields))->getHtml();
        }

        return null;
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

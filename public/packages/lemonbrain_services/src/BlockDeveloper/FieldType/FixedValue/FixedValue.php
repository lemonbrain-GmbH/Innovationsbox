<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\FixedValue;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class FixedValue extends FieldType
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
        return t('FixedValue');
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
        $viewVariable = $field->getViewVariable();
        $inner = $this->getViewData('skip_h', false) === true ? $viewVariable : 'h(' . $viewVariable . ')';

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "") { ?>'
            . $field->getPrefix() . '<?php echo ' . $inner . '; ?>' . $field->getSuffix() . '<?php } ?>';
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return mixed
     */
    public function validate($blockController, $field, $variables = [])
    {
        $rules = [];
        if ($field->isRequired()) {
            $rules[] = 'required';
        }

        return (new Validator($blockController, $field, $variables, $rules))->validate();
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return array
     */
    public function save($blockController, $field, $variables = [])
    {
        $fieldSlug = $field->getSlug();
        $variables['args'][$fieldSlug] = isset($variables['args'][$fieldSlug]) && trim($variables['args'][$fieldSlug]) != ''
            ? $variables['args'][$fieldSlug] : '';

        return $variables;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     * @param array           $miscFields
     *
     * @return string
     */
    public function renderForm($blockController, $field, $variables = [], $miscFields = [])
    {
        $miscFields = array_merge(
            [
                'maxlength'   => $this->getMaxLength(),
                'placeholder' => $this->getPlaceholder(),
            ],
            $miscFields
        );
        h('test');
        $formElements = [];
        $hidden = $field->getValues()['hidden'] ? true : false;
        if ($hidden) {
            $formElements = [
                $field->generateFormContent('fixed_value', $blockController, $variables, $miscFields),
            ];
        } else {
            $formElements = [
                $field->generateFormContent('label', $blockController, $variables),
                $field->generateFormContent('fixed_value', $blockController, $variables, $miscFields),
            ];
        }
        return vsprintf($field->getFormTemplate(), $formElements);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getDatabaseFields($blockController, $field)
    {
        return [
            [
                'name' => $field->getSlug(),
                'type' => 'C',
                'size' => $this->getMaxLength(),
            ],
        ];
    }

    /**
     * @return int
     */
    protected function getMaxLength()
    {
        $maxLength = $this->getConfig('max_length');

        return is_numeric($maxLength) && $maxLength >= 1 && $maxLength <= 255 ? (int) $maxLength : 255;
    }
}

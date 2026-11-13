<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Boolean;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Boolean extends FieldType
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
        return t('Boolean');
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
        $booleanValues = $this->getBooleanValues();
        $inner = sprintf($this->getViewData('skip_t', true) === true ? "%s == 1 ? t('%s') : t('%s')" : "%s == 1 ? '%s' : '%s'", $viewVariable, h($booleanValues[1]), h($booleanValues[0]));

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "") { ?>'
            . $field->getPrefix() . '<?php echo ' . $inner . '; ?>' . $field->getSuffix() . '<?php } ?>';
    }

    /**
     * @param bool $form
     *
     * @return array
     */
    protected function getBooleanValues($form = false)
    {
        $values = [];
        if ($form) {
            $values[''] = sprintf('-- %s --', t('Select'));
            $values[1] = ($y = $this->getConfig('yes_value')) && is_string($y) && trim($y) != '' ? $y : t('Yes');
            $values[0] = ($n = $this->getConfig('no_value')) && is_string($n) && trim($n) != '' ? $n : t('No');
        } else {
            $values[1] = ($y = $this->getViewData('yes_value')) && is_string($y) && trim($y) != '' ? $y : t('Yes');
            $values[0] = ($n = $this->getViewData('no_value')) && is_string($n) && trim($n) != '' ? $n : t('No');
        }

        return $values;
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
        $rules[] = 'boolean';

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
        && in_array($variables['args'][$fieldSlug], [0, 1]) ? $variables['args'][$fieldSlug] : '';

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
        if ($this->isRepeating() && ($defaultValue = $this->getDefaultValue())) {
            $miscFields['data-attr-default-value'] = $defaultValue;
        }
        $miscFields = array_merge(
            [
                'placeholder' => $this->getPlaceholder(),
            ],
            $miscFields
        );
        $variables['optionValues'] = $this->getBooleanValues(true);

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('select', $blockController, $variables, $miscFields),
        ]);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function add($blockController, $field)
    {
        if (!$this->isRepeating() && ($defaultValue = $this->getDefaultValue())) {
            // If we are adding this as a new block (non-repeatable), we would want to set the default value
            $blockController->set($field->getSlug(), $defaultValue);
        }
    }

    protected function getDefaultValue()
    {
        // default_value accepted input are: true, false, 1, 0, "1", and "0"
        $defaultValue = $this->getConfig('default_value');
        if (is_bool($defaultValue)) {
            $defaultValue = $defaultValue === false ? 0 : 1;
        }
        if (trim($defaultValue) != '') {
            return $defaultValue;
        }

        return false;
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
                'size' => 1,
                'attributes' => [
                    'unsigned' => true,
                ],
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Select;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Select extends FieldType
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
        $type = $this->getViewData('type', 'values');
        if ($this->isRepeating()) {
            $fieldSlugOptions = sprintf('%s_%s_options', $field->getParent()->getSlug(), $field->getSlug());
        } else {
            $fieldSlugOptions = $field->getSlug() . '_options';
        }
        switch ($type) {
            case 'switch':
                $cases = [];
                foreach ($this->getSelectValues() as $k => $v) {
                    $cases[] = sprintf('    case "%s":
		// Code for value "%s"
		break;', $k, $v);
                }

                return $this->getViewIndentHTML() . '<?php if (trim(' . $viewVariable . ') != \'\' && array_key_exists(' . $viewVariable . ', $' . $fieldSlugOptions . ')) { ?>'
                    . $field->getPrefix() . '<?php switch(' . $viewVariable . '){ ' . PHP_EOL . implode(PHP_EOL, $cases) . PHP_EOL . '} ?>' . $field->getSuffix() . '<?php } ?>';
                break;
                break;
            case 'key':
                return $this->getViewIndentHTML() . '<?php if (trim(' . $viewVariable . ') != \'\' && array_key_exists(' . $viewVariable . ', $' . $fieldSlugOptions . ')) { ?>'
                    . $field->getPrefix() . '<?php echo ' . $viewVariable . '; ?>' . $field->getSuffix() . '<?php } ?>';
                break;
            case 'value':
            default:
                return $this->getViewIndentHTML() . '<?php if (trim(' . $viewVariable . ') != \'\' && array_key_exists(' . $viewVariable . ', $' . $fieldSlugOptions . ')) { ?>'
                    . $field->getPrefix() . '<?php echo $' . $fieldSlugOptions . '[' . $viewVariable . ']; ?>' . $field->getSuffix() . '<?php } ?>';
                break;
        }
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return mixed|null
     */
    public function view($blockController, $field, $variables = [])
    {
        if ($this->isRepeating()) {
            $fieldSlug = $field->getSlug();

            return isset($variables[$fieldSlug]) ? $variables[$fieldSlug] : null;
        }

        $blockController->set($field->getSlug() . '_options', $this->getSelectValues());

        return null;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return mixed|null
     */
    public function getSearchableContent($blockController, $field, $variables = [])
    {
        if ($field->isSearchable()) {
            $values = $this->getSelectValues();
            $fieldSlug = $field->getSlug();
            $key = isset($variables[$fieldSlug]) ? $variables[$fieldSlug] : $blockController->get($fieldSlug);

            return isset($values[$key]) ? $values[$key] : null;
        }

        return null;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function postView($blockController, $field, $variables = [])
    {
        if ($this->isRepeating()) {
            $fieldSlugOptions = sprintf('%s_%s_options', $field->getParent()->getSlug(), $field->getSlug());
            $blockController->set($fieldSlugOptions, $this->getSelectValues());
        }
    }

    /**
     * @param bool $form
     *
     * @return array
     */
    protected function getSelectValues($form = false)
    {
        $values = $this->getConfig('options');
        if (!is_array($values)) {
            $values = [];
        }
        if ($form) {
            $values = ['' => sprintf('-- %s --', t('Select'))] + $values;
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
        $rules[] = 'in:' . implode(',', array_keys($this->getSelectValues()));

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
        && array_key_exists($variables['args'][$fieldSlug], $this->getSelectValues()) ? $variables['args'][$fieldSlug] : '';

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
        $variables['optionValues'] = $this->getSelectValues(true);

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
        if (trim($defaultValue) != '' && array_key_exists($defaultValue, $this->getSelectValues())) {
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
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Code;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Code extends FieldType
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
        return t('Code');
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
        $inner = $this->getViewData('nl2br', false) === true ? 'nl2br(' . $inner . ')' : $inner;
        $prefix = $field->getPrefix();
        $suffix = $field->getSuffix();
        if ($this->getViewData('skip_pre_code', false) !== true) {
            $prefix .= '<pre><code>';
            $suffix = '</code></pre>' . $suffix;
        }

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "") { ?>'
            . $prefix . '<?php echo ' . $inner . '; ?>' . $suffix . '<?php } ?>';
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
        $variables['args'][$fieldSlug] = isset($variables['args'][$fieldSlug])
        && trim($variables['args'][$fieldSlug]) != '' ? $variables['args'][$fieldSlug] : '';

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
        if (($rows = $this->getConfig('rows')) && is_numeric($rows) && $rows > 0) {
            $miscFields['rows'] = $rows;
        }
        $miscFields = array_merge(
            [
                'placeholder' => $this->getPlaceholder(),
            ],
            $miscFields
        );

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('textArea', $blockController, $variables, $miscFields),
        ]);
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
                'type' => 'X',
            ],
        ];
    }
}

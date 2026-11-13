<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\HashBang;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class HashBang extends FieldType
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
        return t('Hash Bang');
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return string
     */
    public function buildView($blockController, $field)
    {
        $this->setViewIndent();
        $viewVariable = $field->getViewVariable();
        $inner = null;
        if ($this->getViewData('anchor_field', false) === true) {
            $anchorFieldClasses = $this->getViewData('class');
            $anchorFieldClasses = is_string($anchorFieldClasses) && trim($anchorFieldClasses) != '' ?
                sprintf(' class="%s"', $anchorFieldClasses) : null;
            $subject = $this->getViewData('subject');
            $subject = (is_string($subject) && trim($subject) != '') ? sprintf('?subject=%s', $subject) : null;
            $inner = sprintf(
                '\'<a href="/#!\' . ' . $viewVariable . ' . \'%s"%s>\' . ' . $viewVariable . ' . \'</a>\'',
                $subject,
                $anchorFieldClasses
            );
        } else {
            $inner = $viewVariable;
        }

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
        $variables['input-group-text'] = '#!|#|?';
        $miscFields = array_merge(
            [
                'maxlength'   => $this->getMaxLength(),
                'placeholder' => $this->getPlaceholder(),
            ],
            $miscFields
        );

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('text', $blockController, $variables, $miscFields),
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
        return 255;
    }
}

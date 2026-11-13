<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\QuickList;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class QuickList extends FieldType
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
        return t('Quick List');
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
        $elementTag = ($tag = $this->getViewData('elementTag')) && trim($tag) != '' ? $tag : 'ul';
        $itemsTags = ($tagItems = $this->getViewData('itemsTag')) && trim($tagItems) != '' ? $tagItems : 'li';
        $className = ($class = $this->getViewData('class')) && trim($class) != ''
            ? sprintf(' class="%s"', htmlentities(preg_replace('!\s+!', ' ', $class))) : null;
        $valueSlug = $field->getFieldTypeIsRepeating() ? '$' . $field->getParent()->getSlug() . ucfirst($field->getSlug()) . '__value' : '$' . $field->getSlug() . '__value';
        $inner = '<?php foreach(explode("\n", ' . $viewVariable . ') as ' . $valueSlug . '){
			?><' . $itemsTags . '><?php echo ' . $valueSlug . '; ?></' . $itemsTags . '><?php
		} ?>';

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "") { ?>'
            . $field->getPrefix() . '<' . $elementTag . $className . '>' . $inner . '</' . $elementTag . '>' . $field->getSuffix()
            . '<?php } ?>';
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
        $miscFields['rows'] = ($rows = $this->getConfig('rows')) && is_numeric($rows) && $rows > 0 ? $rows : 5;
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

<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Wysiwyg;

use Concrete\Core\Editor\LinkAbstractor;
use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Wysiwyg extends FieldType
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
        return t('WYSIWYG');
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
        $inner = $viewVariable;

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
    public function view($blockController, $field, $variables = [])
    {
        $fieldSlug = $field->getSlug();
        if (isset($variables[$fieldSlug])) {
            return LinkAbstractor::translateFrom($variables[$fieldSlug]);
        }

        $blockController->set($fieldSlug, LinkAbstractor::translateFrom($blockController->get($fieldSlug)));

        return null;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return mixed
     */
    public function edit($blockController, $field, $variables = [])
    {
        $fieldSlug = $field->getSlug();
        if (isset($variables[$fieldSlug])) {
            return LinkAbstractor::translateFromEditMode($variables[$fieldSlug]);
        }

        $blockController->set($fieldSlug, LinkAbstractor::translateFromEditMode($blockController->get($fieldSlug)));

        return null;
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
        && trim($variables['args'][$fieldSlug]) != '' ? LinkAbstractor::translateTo($variables['args'][$fieldSlug]) : '';

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
        $miscFields['class'] = $this->getInputClass($field);
        if (($includePlugins = $this->getConfig('include_plugins')) && is_array($includePlugins) && !empty($includePlugins)) {
            $miscFields['include_plugins'] = $includePlugins;
        }
        if (($excludePlugins = $this->getConfig('exclude_plugins')) && is_array($excludePlugins) && !empty($excludePlugins)) {
            $miscFields['exclude_plugins'] = $excludePlugins;
        }

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('editor', $blockController, $variables, $miscFields),
        ]);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     * @param array           $miscFields
     *
     * @return string
     */
    public function getRepeatableUpdateItemJS($blockController, $field, $variables = [], $miscFields = [])
    {
        return 'blockDeveloperEditor(\'#\' + ($(newField).find(\'textarea.' .
            $this->getInputClass($field) . '\').attr(\'id\')).replace(/(:|\.|\[|\]|,|=|@)/g, "\\\\$1"));';
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     * @param array           $miscFields
     *
     * @return string
     */
    public function getRepeatableDeleteItemJS($blockController, $field, $variables = [], $miscFields = [])
    {
        return 'if(typeof CKEDITOR !== \'undefined\'){
                    var blockDesignerEditorInstance = CKEDITOR.instances[$(sortableItem).find(\'textarea.'
            . $this->getInputClass($field) . '\').attr(\'id\')];
                    if (blockDesignerEditorInstance) {
                        blockDesignerEditorInstance.destroy();
                    }
                }';
    }

    /**
     * @param Field $field
     *
     * @return string
     */
    protected function getInputClass($field)
    {
        if ($field->getFieldTypeIsRepeating()) {
            return 'block-developer-wysiwyg-' . $field->getParent()->getSlug() . '-' . $field->getSlug();
        }

        return 'block-developer-wysiwyg-' . $field->getSlug();
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
                'type' => 'X2',
            ],
        ];
    }
}

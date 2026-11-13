<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Url;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldTypeList;

class Url extends FieldType
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
        return t('Url');
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
        $newWindow = ($nw = $this->getViewData('new_window')) && (bool) $nw === true ? ' target="_blank"' : null;
        $class = ($c = $this->getViewData('class')) && is_string($c) && trim($c) != '' ? sprintf(' class="%s"', $c) : null;
        $title = $viewVariable;
        if ($this->useTitleField()) {
            $slugTitle = $this->getSlugTitle($field);
            if ($field->getFieldTypeIsRepeating()) {
                $titleVar = $field->getParent()->getViewVariable() . '_item[\'' . $slugTitle . '\']';
            } else {
                $titleVar = '$' . $slugTitle;
            }
            $title = '(isset(' . $titleVar . ') && trim(' . $titleVar . ') != "" ? ' . $titleVar . ' : ' . $viewVariable . ')';
        }
        $inner = "'<a href=\"' . " . $viewVariable . " . '\"" . $newWindow . $class . ">' . " . $title . " . '</a>'";

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "") { ?>' .
            $field->getPrefix() . '<?php echo ' . $inner . '; ?>' . $field->getSuffix() . '<?php } ?>';
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
        $rules[] = 'url';

        $return = (new Validator($blockController, $field, $variables, $rules))->validate();

        if ($this->useTitleField() && $this->getTitleFieldRequired()) {
            $rules = ['required'];
            $fieldTitle = $this->getTitleField($field);
            $return = array_merge($return, (new Validator($blockController, $fieldTitle, $variables, $rules))->validate());
        }

        return $return;
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
        $fieldSlugTitle = $this->getSlugTitle($field);
        if ($this->useTitleField()) {
            $variables['args'][$fieldSlugTitle] = isset($variables['args'][$fieldSlugTitle])
            && trim($variables['args'][$fieldSlugTitle]) != '' ? $variables['args'][$fieldSlugTitle] : '';
        }

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
        $variables['input-group-text'] = ($igt = $this->getInputGroupText()) ? $igt : '<i class="fa fa-link"></i>';
        $miscFields = array_merge(
            [
                'placeholder' => $this->getPlaceholder(),
                'maxlength'   => 2000,
            ],
            $miscFields
        );

        $html = vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('text', $blockController, $variables, array_filter($miscFields)),
        ]);

        if ($this->useTitleField()) {
            unset($variables['input-group-text']);
            $miscFieldsTitle = ['maxlength' => 255];
            $fieldTitle = $this->getTitleField($field);
            $html .= vsprintf($fieldTitle->getFormTemplate(), [
                $fieldTitle->generateFormContent('label', $blockController, $variables),
                $fieldTitle->generateFormContent('text', $blockController, $variables, $miscFieldsTitle),
            ]);
        }

        return $html;
    }

    /**
     * @param Field $field
     *
     * @return Field
     */
    protected function getTitleField($field)
    {
        $v = [
            'label' => $field->getLabel() . ' ' . t('Title'),
        ];
        if ($this->getTitleFieldRequired()) {
            $v['required'] = true;
        }
        /* @var $fieldType FieldType */
        $fieldType = FieldTypeList::getFieldTypeClassByHandle('Text');
        if ($field->getFieldTypeIsRepeating()) {
            $fieldType->setRepeating(true);
        }
        $fieldTitle = new Field($this->getSlugTitle($field), $v, $fieldType);
        if ($field->getFieldTypeIsRepeating()) {
            $fieldTitle->setParent($field->getParent());
        }

        return $fieldTitle;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getDatabaseFields($blockController, $field)
    {
        $fields = [
            [
                'name' => $field->getSlug(),
                'type' => 'C',
                'size' => 2000,
            ],
        ];
        if ($this->useTitleField()) {
            $fields[] = [
                'name' => $this->getSlugTitle($field),
                'type' => 'C',
                'size' => 255,
            ];
        }

        return $fields;
    }

    /**
     * @return bool
     */
    protected function useTitleField()
    {
        return ($titleField = $this->getConfig('title_field')) && is_bool($titleField) && $titleField === true;
    }

    /**
     * @return bool
     */
    protected function getTitleFieldRequired()
    {
        return ($required = $this->getConfig('title_field_required')) && is_bool($required) && $required === true;
    }

    /**
     * @param $field Field
     *
     * @return string
     */
    protected function getSlugTitle($field)
    {
        return $field->getSlug() . '__title';
    }
}

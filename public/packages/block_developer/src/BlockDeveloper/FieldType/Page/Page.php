<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Page;

use Concrete\Core\Page\Page as CorePage;
use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldTypeList;

class Page extends FieldType
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
        return t('Page');
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
        $title = $viewVariable . '->getCollectionName()';
        if ($this->useTitleField()) {
            $slugTitle = $this->getSlugTitle($field);
            if ($field->getFieldTypeIsRepeating()) {
                $titleVar = $field->getParent()->getViewVariable() . '_item[\'' . $slugTitle . '\']';
            } else {
                $titleVar = '$' . $slugTitle;
            }
            $title = '(isset(' . $titleVar . ') && trim(' . $titleVar . ') != "" ? ' . $titleVar . ' : ' . $title . ')';
        }
        $inner = "'<a href=\"' . " . $viewVariable . "->getCollectionLink() . '\"" . $newWindow . $class . ">' . " . $title . " . '</a>'";

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && is_object(' . $viewVariable . ')) { ?>' .
            $field->getPrefix() . '<?php echo ' . $inner . '; ?>' . $field->getSuffix() . '<?php } ?>';
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
        $fieldSlug = $field->getSlug();
        if ($this->isRepeating()) {
            return $this->getPageObject(isset($variables[$fieldSlug]) ? $variables[$fieldSlug] : null);
        }

        $pageObject = $blockController->get($fieldSlug);
        if (!($pageObject instanceof CorePage)) {
            $pageObject = $this->getPageObject($blockController->get($fieldSlug));
        }
        $blockController->set($field->getSlug(), $pageObject);

        return null;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     */
    public function addEdit($blockController, $fieldRepeatable, $variables = [])
    {
        $blockController->requireAsset('core/sitemap');
    }

    /**
     * @param $cID
     *
     * @return bool|CorePage
     */
    protected function getPageObject($cID = null)
    {
        $page = false;
        if (is_numeric($cID) && $cID > 0 && ($po = CorePage::getByID($cID)) && !$po->isError() && !$po->isInTrash()) {
            $page = $po;
        }

        return $page;
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
            $rules[] = 'page:required';
        } else {
            $rules[] = 'page';
        }

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
     * @param array           $miscFields
     *
     * @return string
     */
    public function getRepeatableUpdateItemJS($blockController, $field, $variables = [], $miscFields = [])
    {
        $fieldSlug = $field->getSlug();
        $variable = 'ftPage' . ucfirst($fieldSlug);
        $js = 'Concrete.Vue.activateContext(\'cms\', function (Vue, config) {
                    new Vue({
                        el: \'#\' + $(' . $variable . ').attr(\'id\'),
                        components: config.components
                    });
                });';
        // Versions below 9.x have the "concretePageSelector" available
        if (version_compare(Config::get('concrete.version'), '9.0', '<')) {
            $js = 'var ' . $fieldSlug . 'ID = $(' . $variable . ').attr(\'data-page-selector-page-id\');
                $(' . $variable . ').concretePageSelector({
	                inputName: $(' . $variable . ').attr(\'data-page-selector-input-name\'), 
	                cID : ' . $fieldSlug . 'ID != \'0\' ? ' . $fieldSlug . 'ID : \'\'
                });';
        }

        return 'var ' . $variable . ' = $(newField).find(\'.' . $this->getInputClass($field) . '\');
            if ($(' . $variable . ').length > 0) {
                ' . $js . '
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
            return 'ft-page-' . $field->getParent()->getSlug() . '-' . $field->getSlug() . '-page-selector';
        }

        return 'ft-page-' . $field->getSlug() . '-page-selector';
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
        && trim($variables['args'][$fieldSlug]) != '' ? $variables['args'][$fieldSlug] : 0;
        if ($this->useTitleField()) {
            $fieldSlugTitle = $this->getSlugTitle($field);
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
        $miscFields = array_merge(
            [
                'placeholder' => $this->getPlaceholder(),
            ],
            $miscFields
        );

        $miscFields['class'] = 'ccm-page-selector ' . $this->getInputClass($field);
        $html = vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('page', $blockController, $variables, $miscFields),
        ]);

        if ($this->useTitleField()) {
            $miscFieldsTitle = [];
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
                'type' => 'I',
                'size' => 11,
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
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getExportPageColumns($blockController, $field)
    {
        return [$field->getSlug()];
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

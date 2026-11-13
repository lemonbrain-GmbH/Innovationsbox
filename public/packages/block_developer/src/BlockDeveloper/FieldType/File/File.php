<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\File;

use Concrete\Core\File\File as CoreFile;
use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class File extends FieldType
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
        return t('File');
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return \Concrete\Core\Entity\File\File|null
     */
    public function view($blockController, $field, $variables = [])
    {
        $fieldSlug = $field->getSlug();
        if (isset($variables[$fieldSlug])) {
            if ($f = CoreFile::getByID($variables[$fieldSlug])) {
                return $f;
            }

            return null;
        }

        $blockController->set($fieldSlug, CoreFile::getByID($blockController->get($fieldSlug)));

        return null;
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
        $fieldData = [
            'src' => $viewVariable . '->getRelativePath()',
            'title' => $viewVariable . '->getTitle()',
            'class' => ($class = $this->getViewData('class')) && trim($class) != ''
                ? htmlentities(preg_replace('!\s+!', ' ', $class)) : null,
            'target' => ($target = $this->getViewData('target')) && trim($target) != '' ? h($target) : null,
        ];
        if ($this->getViewData('output_src_only') === true) {
            $img = '<?php echo ' . $fieldData['src'] . '; ?>';
        } else {
            $class = trim($fieldData['class']) != '' ? sprintf(' class="%s"', $fieldData['class']) : null;
            $target = trim($fieldData['target']) != '' ? sprintf(' target="%s"', $fieldData['target']) : null;
            $img = sprintf(
                '<a href="<?php echo %s; ?>"%s%s><?php echo %s; ?></a>',
                $fieldData['src'],
                $class,
                $target,
                $fieldData['title']
            );
        }

        return $this->getViewIndentHTML() . '<?php if (is_object(' . $viewVariable . ') && ($_fp = new Permissions(' . $viewVariable . ')) 
		&& $_fp->canViewFile()) { ?>' .
            $field->getPrefix() . $img . $field->getSuffix() . '<?php } ?>';
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
        $rules[] = 'file';

        return (new Validator($blockController, $field, $variables, $rules))->validate();
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
        return null;
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
            ? $variables['args'][$fieldSlug] : 0;

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
        $fieldSlug = $field->getSlug();
        if (isset($variables[$fieldSlug])) {
            $fID = $variables[$fieldSlug];
        } else {
            $fID = $blockController->get($fieldSlug);
        }
        $fileInstanceBlock = CoreFile::getByID($fID);
        if (!is_object($fileInstanceBlock)) {
            $fileInstanceBlock = null;
        }
        $variables['fileInstanceBlock'] = $fileInstanceBlock;
        $miscFields['class'] = 'ccm-file-selector ' . $this->getInputClass($field);

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('file', $blockController, $variables, $miscFields),
        ]);
    }

    /**
     * @param Field $field
     *
     * @return string
     */
    protected function getInputClass($field)
    {
        if ($field->getFieldTypeIsRepeating()) {
            return 'ft-file-' . $field->getParent()->getSlug() . '-' . $field->getSlug() . '-file-selector';
        }

        return 'ft-file-' . $field->getSlug() . '-file-selector';
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
        $variable = 'ftFile' . ucfirst($fieldSlug);
        $js = 'Concrete.Vue.activateContext(\'cms\', function (Vue, config) {
                    new Vue({
                        el: \'#\' + $(' . $variable . ').attr(\'id\'),
                        components: config.components
                    });
                });';
        // Versions below 9.x have the "concreteFileSelector" available
        if (version_compare(Config::get('concrete.version'), '9.0', '<')) {
            $js = 'var ' . $fieldSlug . 'ID = $(' . $variable . ').attr(\'data-file-selector-f-id\');
                $(' . $variable . ').concreteFileSelector({
	                inputName: $(' . $variable . ').attr(\'data-file-selector-input-name\'), 
	                filters: [], 
	                fID : ' . $fieldSlug . 'ID != \'0\' ? ' . $fieldSlug . 'ID : \'\'
                });';
        }

        return 'var ' . $variable . ' = $(newField).find(\'.' . $this->getInputClass($field) . '\');
            if ($(' . $variable . ').length > 0) {
                ' . $js . '
            }';
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
                'type' => 'I',
                'attributes' => [
                    'default' => '0',
                    'notnull' => true,
                ],
            ],
        ];
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getExportFileColumns($blockController, $field)
    {
        return [$field->getSlug()];
    }

    public function edit($blockController, $field, $variables = [])
    {
        $fieldSlug = $field->getSlug();
        if (isset($variables[$fieldSlug]) && CoreFile::getByID($variables[$fieldSlug])) {
            return $variables[$fieldSlug];
        }

        return null;
    }
}

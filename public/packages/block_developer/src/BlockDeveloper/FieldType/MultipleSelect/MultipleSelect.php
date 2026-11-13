<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\MultipleSelect;

use Devoda\BlockDeveloper\BlockDeveloper;
use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;
use Doctrine\DBAL\Connection;

class MultipleSelect extends FieldType
{
    /**
     * @var bool
     */
    protected $repeatable = false;

    /**
     * @var null
     */
    protected $multipleSelectEntriesTable = null;

    /**
     * @return string
     */
    public function getFieldTypeName()
    {
        return t('Multiple Select');
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function constructClass($blockController, $field)
    {
        $slug = $field->getSlug();
        $this->multipleSelectEntriesTable = $blockController->getBlockTypeDatabaseTable()
            . BlockDeveloper::_nameSpace($slug) . 'Entries';
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
        $slug = $field->getSlug();
        $viewVariable = $field->getViewVariable();
        $type = $this->getViewData('type', 'values');
        switch ($type) {
            case 'keys_custom':
            case 'values_custom':
                $inner = '<?php foreach(' . $viewVariable . ' as $' . $slug . '_key => $' . $slug . '_value){ ?>'
                    . $this->getViewData('type_prefix');
                if ($type == 'values_custom') {
                    $inner .= '<?php echo $' . $slug . '_value; ?>';
                } else {
                    $inner .= '<?php echo $' . $slug . '_key; ?>';
                }
                $inner .= $this->getViewData('type_suffix') . '<?php } ?>';
                break;
            case 'keys':
                $inner = '<?php echo implode(\', \', array_keys($' . $slug . ')); ?>';
                break;
            default:
            case 'values':
                $inner = '<?php echo implode(\', \', $' . $slug . '); ?>';
                break;
        }

        return $this->getViewIndentHTML() . '<?php if (!empty(' . $viewVariable . ')) { ?>'
            . $field->getPrefix() . $inner . $field->getSuffix() . '<?php } ?>';
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function view($blockController, $field, $variables = [])
    {
        $fieldSlug = $field->getSlug();
        $blockController->set($fieldSlug, $this->getMultipleSelectSelections($blockController, $this->getMultipleSelectSelectionsTable(), $variables['bID'], $this->getConfig('options', [])));
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function addEdit($blockController, $field, $variables = [])
    {
        $blockController->requireAsset('javascript', 'select2');
        $blockController->requireAsset('css', 'select2');
        if ($this->chosenForSortable()) {
            $variables['al']->register('javascript', 'select2sortable', 'js/select2.sortable.js', [], 'block_developer');
            $blockController->requireAsset('javascript', 'select2sortable');
        }
        $variables['al']->register('css', 'block-developer-multipleSelect-field-type', 'css/multipleSelect-ft.form.css', [], 'block_developer');
        $blockController->requireAsset('css', 'block-developer-multipleSelect-field-type');
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
            $rules[] = 'required_array';
        }
        $options = $this->getConfig('options', []);
        if (!empty($options)) {
            $rules[] = 'array_in:' . implode(',', array_keys($options));
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
        /* @var $db Connection */
        $db = $blockController->getApplication()->make('database')->connection();
        $fieldSlug = $field->getSlug();
        $table = $this->getMultipleSelectSelectionsTable();
        $bID = $blockController->getBlockObject()->getBlockID();
        $entries = $this->getMultipleSelectSelections($blockController, $table, $bID, [], true);
        if (isset($variables['args'][$fieldSlug]) && is_array($variables['args'][$fieldSlug])) {
            $sortOrder = 1;
            foreach ($variables['args'][$fieldSlug] as $value) {
                $data = [
                    'value' => $value,
                    'sortOrder' => $sortOrder,
                    'bvID' => $bID,
                ];
                if (!empty($entries)) {
                    $entryID = key($entries);
                    $db->update($table, $data, ['msID' => $entryID]);
                    unset($entries[$entryID]);
                } else {
                    $db->insert($table, $data);
                }
                ++$sortOrder;
            }
            unset($variables['args'][$fieldSlug]);
        }
        if (!empty($entries)) {
            foreach (array_keys($entries) as $entry) {
                $db->delete($table, ['msID' => $entry]);
            }
        }

        return $variables;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function delete($blockController, $field)
    {
        /* @var $db Connection */
        $db = $blockController->getApplication()->make('database')->connection();
        $dbTable = $this->getMultipleSelectSelectionsTable();
        $db->executeQuery('DELETE FROM ' . $dbTable . ' WHERE bvID = ?', [$blockController->get('bID')]);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return string
     */
    public function getSearchableContent($blockController, $field, $variables = [])
    {
        $content = [];
        if ($field->isSearchable()) {
            $values = $this->getMultipleSelectSelections($blockController, $this->getMultipleSelectSelectionsTable(), $blockController->get('bID'), $this->getConfig('options', []));
            $type = $this->getViewData('type', 'values_custom');
            if (in_array($type, ['values', 'values_custom'])) {
                foreach ($values as $v) {
                    $content[] = $v;
                }
            } else {
                $content = array_keys($values);
            }
        }

        return implode(' ', $content);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function duplicate($blockController, $field, $variables = [])
    {
        /* @var $db Connection */
        $db = $blockController->getApplication()->make('database')->connection();
        $table = $this->getMultipleSelectSelectionsTable();
        $bID = $blockController->getBlockControllerData()->bID;
        $entries = $this->getMultipleSelectSelections($blockController, $table, $bID, [], true, false);
        foreach ($entries as $entry) {
            unset($entry['msID']);
            $entry['bvID'] = $variables['newBID'];
            $db->insert($table, $entry);
        }
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
        $action = $blockController->getAction();
        $someMultipleSelect = [];
        $someMultipleSelect_options = $this->getConfig('options', []);
        switch ($action) {
            case 'edit':
                $someMultipleSelect = $this->getMultipleSelectSelections($blockController, $this->getMultipleSelectSelectionsTable(), $blockController->get('bID'), $someMultipleSelect_options);
                break;
            case 'add':
                $someMultipleSelect_defaults = $this->getViewData('defaults');
                // If "defaults" are set, it's an array and not empty
                if (is_array($someMultipleSelect_defaults) && !empty($someMultipleSelect_defaults)) {
                    // Get them (unique ones) and add them, since we want them selected by default
                    foreach (array_unique($someMultipleSelect_defaults) as $someMultipleSelect_default) {
                        if (isset($someMultipleSelect_options[$someMultipleSelect_default])) {
                            $someMultipleSelect[$someMultipleSelect_default] = $someMultipleSelect_options[$someMultipleSelect_default];
                        }
                    }
                }
                break;
        }
        $bindString = $blockController->getBlockTypeDatabaseTable() . '.' . $fieldSlug . '.multipleSelect';
        $selectorString = 'select[name="' . $fieldSlug . '[]"]';
        if ($this->chosenForSortable()) {
            $inner = '        $(\'' . $selectorString . '\').select2_sortable();
        $(\'' . $selectorString . '\').on("select2-selecting", function (evt) {
            var $element = $(evt.object.element);
            $element.detach();
            $(this).append($element);
            $(this).trigger("change");
        });';
        } else {
            $inner = '        $(\'' . $selectorString . '\').select2();';
        }
        $assets = '<script type="text/javascript">
    Concrete.event.bind(\'' . $bindString . '\', function () {
        $(document).ready(function () {
            ' . $inner . '
        });
    });
    
    Concrete.event.publish(\'' . $bindString . '\');
</script>';

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('selectMultiple', $blockController, $variables, [
                'optionValues' => $someMultipleSelect + $someMultipleSelect_options,
                'defaultValues' => array_keys($someMultipleSelect),
            ]),
        ]) . $assets;
    }

    /**
     * @return bool
     */
    private function chosenForSortable()
    {
        return $this->getConfig('sortable') === true ? true : false;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getDatabaseTables($blockController, $field)
    {
        return [
            $this->getMultipleSelectSelectionsTable() => [
                'fields' => [
                    [
                        'name' => 'msID',
                        'type' => 'I',
                        'attributes' => [
                            'key' => true,
                            'unsigned' => true,
                            'autoincrement' => true,
                        ],
                    ],
                    [
                        'name' => 'bvID',
                        'type' => 'I',
                    ],
                    [
                        'name' => 'value',
                        'type' => 'C',
                    ],
                    [
                        'name' => 'sortOrder',
                        'type' => 'I',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getMultipleSelectSelectionsTable()
    {
        return $this->multipleSelectEntriesTable;
    }

    /**
     * @param BlockController $blockController
     * @param string          $table
     * @param string          $bvID
     * @param array           $options
     * @param bool            $save
     * @param bool            $process
     *
     * @return array
     */
    protected function getMultipleSelectSelections($blockController, $table, $bvID, $options = [], $save = false, $process = true)
    {
        $return = [];
        if (trim($bvID) != '' && $bvID > 0) {
            /* @var $db Connection */
            $db = $blockController->getApplication()->make('database')->connection();
            if (($items = $db->fetchAll('SELECT * FROM ' . $table . ' WHERE bvID = ? ORDER BY sortOrder', [$bvID]))) {
                if ($process) {
                    foreach ($items as $item) {
                        if ($save) {
                            $return[$item['msID']] = $item['value'];
                        } elseif (isset($options[$item['value']])) {
                            $return[$item['value']] = $options[$item['value']];
                        }
                    }
                } else {
                    $return = $items;
                }
            }
        }

        return $return;
    }
}

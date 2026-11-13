<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\Repeatable;

use Devoda\BlockDeveloper\BlockDeveloper;
use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Repeatable extends FieldType
{
    /**
     * @var string
     */
    protected $repeatableEntriesTable = '';

    /**
     * @var bool
     */
    protected $repeatable = true;

    /**
     * @var array
     */
    protected $blockDeveloperFields = [];

    /**
     * @return string
     */
    public function getFieldTypeName()
    {
        return t('Repeatable');
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     */
    public function constructClass($blockController, $fieldRepeatable)
    {
        $this->repeatableEntriesTable = $blockController->getBlockTypeDatabaseTable()
            . BlockDeveloper::_nameSpace($fieldRepeatable->getSlug()) . 'Entries';

        $dataFields = ($configFields = $this->getConfig('fields')) && is_array($configFields) ? $configFields : [];
        $this->blockDeveloperFields = $blockController->processBlockDeveloperFields($dataFields, $fieldRepeatable);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     */
    public function view($blockController, $fieldRepeatable, $variables = [])
    {
        $items = $this->getRepeatableItems($blockController, $variables['bID']);
        foreach ($items as &$item) {
            foreach ($this->getFields() as $fieldSlug => $field) {
                /* @var $fieldType FieldType */
                $fieldType = $field->getFieldType();
                if (method_exists($fieldType, 'view')) {
                    $item[$fieldSlug] = $fieldType->view($blockController, $field, array_merge($variables, $item));
                }
            }
        }
        foreach ($this->getFields() as $fieldSlug => $field) {
            $fieldType = $field->getFieldType();
            if (method_exists($fieldType, 'postView')) {
                $fieldType->postView($blockController, $field, $variables);
            }
        }
        $blockController->set($fieldRepeatable->getSlug(), $items);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     *
     * @return string
     */
    public function buildView($blockController, $fieldRepeatable)
    {
        $viewVariable = $fieldRepeatable->getViewVariable();
        $html = '<?php if (is_array(' . $viewVariable . ') && !empty(' . $viewVariable . ')) { ?>'
            . $fieldRepeatable->getPrefix() . '<?php foreach (' . $viewVariable . ' as '
            . $viewVariable . '_item_key => ' . $viewVariable . '_item) { ?>';
        if (($childPrefix = $this->getViewData('child_prefix')) && is_string($childPrefix) && trim($childPrefix) != '') {
            $html .= $childPrefix;
        }
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            $html .= $fieldType->buildView($blockController, $field);
        }
        if (($childSuffix = $this->getViewData('child_suffix')) && is_string($childSuffix) && trim($childSuffix) != '') {
            $html .= $childSuffix;
        }
        $html .= '<?php } ?>' . $fieldRepeatable->getSuffix() . '<?php } ?>';

        return $html;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->blockDeveloperFields;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     */
    public function addEdit($blockController, $fieldRepeatable, $variables = [])
    {
        $variables['al']->register('css', 'block-developer-repeatable-field-type', 'css/repeatable-ft.form.css', [], 'block_developer');
        $variables['al']->register('javascript', 'handlebars', 'js/handlebars-v4.0.11.js', [], 'block_developer');
        $variables['al']->register('javascript', 'handlebars-helpers-block-developer', 'js/handlebars-helpers.js', [], 'block_developer');

        $blockController->requireAsset('css', 'block-developer-repeatable-field-type');
        $blockController->requireAsset('javascript', 'handlebars');
        $blockController->requireAsset('javascript', 'handlebars-helpers-block-developer');

        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            if (method_exists($fieldType, 'addEdit')) {
                $fieldType->addEdit($blockController, $field, $variables);
            }
        }
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     */
    public function add($blockController, $fieldRepeatable)
    {
        $blockController->set($fieldRepeatable->getSlug(), []);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     */
    public function edit($blockController, $fieldRepeatable)
    {
        $items = $this->getRepeatableItems($blockController, $blockController->get('bID'));
        foreach ($items as &$item) {
            foreach ($this->getFields() as $fieldSlug => $field) {
                /* @var $fieldType FieldType */
                $fieldType = $field->getFieldType();
                if (method_exists($fieldType, 'edit')) {
                    $item[$fieldSlug] = $fieldType->edit($blockController, $field, $item);
                }
            }
        }
        $blockController->set($fieldRepeatable->getSlug(), $items);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     * @param array           $miscFields
     *
     * @return string
     */
    public function renderForm($blockController, $fieldRepeatable, $variables = [], $miscFields = [])
    {
        $fieldRepeatableSlug = $fieldRepeatable->getSlug();
        $items = $blockController->get($fieldRepeatableSlug);
        $html = null;
        $eventBindSelector = sprintf('%s.%s.edit.open', $blockController->getBlockTypeDatabaseTable() . time(), $fieldRepeatable->getSlug());
        $repeatableContainerID = $blockController->getBlockTypeDatabaseTable() . '-' . $fieldRepeatable->getSlug() . '-items-container-';
        $titleMeFieldSlug = $this->getConfig('title_field');
        $repeatableUpdateItemJS = null;
        $repeatableDeleteItemJS = null;
        $vars = ['view' => $variables['view']];
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $field Field */
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            $miscFields = [];
            if ($fieldSlug == $titleMeFieldSlug) {
                $miscFields['class'] = 'form-control title-me';
            }
            $html .= $fieldType->renderForm($blockController, $field, $vars, $miscFields);
            if (method_exists($fieldType, 'getRepeatableUpdateItemJS')
                && ($getRepeatableUpdateItemJS = $fieldType->getRepeatableUpdateItemJS($blockController, $field, $vars, $miscFields))
                && trim($getRepeatableUpdateItemJS) != '') {
                $repeatableUpdateItemJS .= $getRepeatableUpdateItemJS;
            }
            if (method_exists($fieldType, 'getRepeatableDeleteItemJS')
                && ($getRepeatableDeleteItemJS = $fieldType->getRepeatableDeleteItemJS($blockController, $field, $vars, $miscFields))
                && trim($getRepeatableDeleteItemJS) != '') {
                $repeatableDeleteItemJS .= $getRepeatableDeleteItemJS;
            }
        }
        $addLabel = $this->getConfig('add_label', t('Add Entry'));
        $slideSpeed = ($ss = $this->getConfig('slide_speed')) && is_numeric($ss) && $ss >= 0 ? $ss : 200;
        // In case of an empty string, set the add label to "Add Entry"
        if (trim($addLabel) == '') {
            $addLabel = t('Add Entry');
        }
        $config = \Core::make('site')->getDefault()->getConfigRepository();
        $editor = $blockController->getApplication()->make('editor');
        $editor->getPluginManager()->select($config->get('editor.ckeditor4.plugins.selected_default'));

        return sprintf($fieldRepeatable->getFormTemplate(), '', $this->inc('form.php', [
            'addLabel' => $addLabel,
            'items' => $items,
            'html' => $html,
            'editorInitJSFunction' => $editor->getEditorInitJSFunction(),
            'repeatableUpdateItemJS' => $repeatableUpdateItemJS,
            'repeatableDeleteItemJS' => $repeatableDeleteItemJS,
            'eventBindSelector' => $eventBindSelector,
            'repeatableContainerID' => $repeatableContainerID,
            'fieldRepeatable' => $fieldRepeatable,
            'slideSpeed' => $slideSpeed,
            'noEntriesInfo' => t('No entries entered yet.'),
            'confirmDeleteText' => t('Are you sure'),
        ]));
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     *
     * @return array
     */
    public function validate($blockController, $fieldRepeatable, $variables = [])
    {
        $errors = [];
        $minEntries = $this->getConfig('minimum_entries');
        $itemsEntriesMin = is_numeric($minEntries) && $minEntries > 0 ? $minEntries : 0;
        $maxEntries = $this->getConfig('maximum_entries');
        $itemsEntriesMax = is_numeric($maxEntries) && $maxEntries > 0 && $maxEntries >= $minEntries ? $maxEntries : 0;
        $fieldSlug = $fieldRepeatable->getSlug();
        $label = $fieldRepeatable->getLabel();
        if (isset($variables['args'][$fieldSlug]) && is_array($variables['args'][$fieldSlug])
            && !empty($variables['args'][$fieldSlug])
        ) {
            if ($itemsEntriesMin >= 1 && count($variables['args'][$fieldSlug]) < $itemsEntriesMin) {
                $errors[] = t(
                    'The %s field requires at least %s entries, %s entered.',
                    $label,
                    $itemsEntriesMin,
                    count($variables['args'][$fieldSlug])
                );
            }
            if ($itemsEntriesMax >= 1 && count($variables['args'][$fieldSlug]) > $itemsEntriesMax) {
                $errors[] = t(
                    'The %s field is set to a maximum of %s entries, %s entered.',
                    $label,
                    $itemsEntriesMax,
                    count($variables['args'][$fieldSlug])
                );
            }
            if (empty($errors)) {
                foreach ($variables['args'][$fieldSlug] as $items_k => $items_v) {
                    if (is_array($items_v)) {
                        foreach ($this->getFields() as $fieldSlug => $field) {
                            /* @var $field Field */
                            /* @var $fieldType FieldType */
                            $fieldType = $field->getFieldType();
                            $fVariables = ['args' => $items_v, 'row' => $items_k];
                            if ($result = $fieldType->validate($blockController, $field, $fVariables)) {
                                if (is_string($result)) {
                                    $errors[] = $result;
                                } elseif (is_array($result) && !empty($result)) {
                                    $errors = array_merge($errors, $result);
                                }
                            }
                        }
                    } else {
                        $errors[] = t('The values for the %s field, row #%s, are incomplete.', $label, $items_k);
                    }
                }
            }
        } else {
            if ($itemsEntriesMin >= 1) {
                $errors[] = t('The %s field requires at least %s entries, none entered.', $label, $itemsEntriesMin);
            }
        }

        return $errors;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     *
     * @return string
     */
    public function getSearchableContent($blockController, $fieldRepeatable, $variables = [])
    {
        $items = $this->getRepeatableItems($blockController, $blockController->get('bID'));
        $content = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                foreach ($this->getFields() as $fieldSlug => $field) {
                    if ($result = $field->getFieldType()->getSearchableContent($blockController, $field, $item)) {
                        $content[] = $result;
                    }
                }
            }
        }

        return implode(' ', $content);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     *
     * @return array
     */
    public function save($blockController, $fieldRepeatable, $variables = [])
    {
        $fieldSlug = $fieldRepeatable->getSlug();
        $dbTable = $this->getRepeatableEntriesTable();
        $bID = $blockController->getBlockObject()->getBlockID();
        $rows = $this->getRepeatableItems($blockController, $bID);
        $items = isset($variables['args'][$fieldSlug]) && is_array($variables['args'][$fieldSlug])
        && !empty($variables['args'][$fieldSlug]) ? $variables['args'][$fieldSlug] : [];
        $queries = [];
        if (!empty($items)) {
            $sortOrder = 1;
            foreach ($items as $item) {
                foreach ($this->getFields() as $fieldSlug => $field) {
                    // Check if we get a result back
                    if ($result = $field->getFieldType()->save($this, $field, ['args' => $item])) {
                        // And be sure the "args" key is set and is an array here
                        if (is_array($result) && isset($result['args']) && is_array($result['args'])) {
                            $item = $result['args'];
                        }
                    }
                }
                $item['sortOrder'] = $sortOrder;
                if (!empty($rows)) {
                    $rowsKey = key($rows);
                    $queries['update'][$rows[$rowsKey]['id']] = $item;
                    unset($rows[$rowsKey]);
                } else {
                    $item['bID'] = $bID;
                    $queries['insert'][] = $item;
                }
                ++$sortOrder;
            }
        }
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $queries['delete'][] = $row['id'];
            }
        }
        $this->executeQueries($blockController, $queries, $dbTable);

        return $variables;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     *
     * @return array
     */
    public function getDatabaseTables($blockController, $fieldRepeatable)
    {
        $tables = [];
        // Define the base fields for the table
        $fields = [
            [
                'name' => 'id',
                'type' => 'I',
                'attributes' => [
                    'key' => true,
                    'unsigned' => true,
                    'autoincrement' => true,
                ],
            ],
            [
                'name' => 'bID',
                'type' => 'I',
                'attributes' => [
                    'unsigned' => true,
                ],
            ],
        ];
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            $fields = array_merge($fields, $fieldType->getDatabaseFields($blockController, $field));
            $tables = array_merge($tables, $fieldType->getDatabaseTables($blockController, $field));
        }
        // Add the sort order field as last one
        $fields[] = [
            'name' => 'sortOrder',
            'type' => 'I',
        ];

        return array_merge([
            $this->getRepeatableEntriesTable() => [
                'fields' => $fields,
            ],
        ], $tables);
    }

    /**
     * @param BlockController $blockController
     * @param int             $bID
     *
     * @return array
     */
    protected function getRepeatableItems($blockController, $bID = 0)
    {
        $items = [];
        if ($bID > 0) {
            $db = $blockController->getApplication()->make('database')->connection();
            $items = $db->fetchAll('SELECT * 
									FROM ' . $this->getRepeatableEntriesTable() . ' 
									WHERE bID = ? 
									ORDER BY sortOrder', [$bID]);
        }

        return $items;
    }

    /**
     * @return string
     */
    private function getRepeatableEntriesTable()
    {
        return $this->repeatableEntriesTable;
    }

    /**
     * @param $blockController
     * @param $queries
     * @param $dbTable
     */
    protected function executeQueries($blockController, $queries, $dbTable)
    {
        if (!empty($queries)) {
            /* @var $db \Doctrine\DBAL\Connection */
            $db = $blockController->getApplication()->make('database')->connection();
            foreach ($queries as $type => $values) {
                if (!empty($values)) {
                    switch ($type) {
                        case 'update':
                            foreach ($values as $id => $data) {
                                $db->update($dbTable, $data, ['id' => $id]);
                            }
                            break;
                        case 'insert':
                            foreach ($values as $data) {
                                $db->insert($dbTable, $data);
                            }
                            break;
                        case 'delete':
                            foreach ($values as $value) {
                                $db->delete($dbTable, ['id' => $value]);
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getExportPageColumns($blockController, $field)
    {
        $btExportPageColumns = [];
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $field Field */
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            // The "getExportPageColumns" function isn't there by default, because only a few need to be exported
            if (method_exists($fieldType, 'getExportPageColumns')) {
                $btExportPageColumns = array_merge($btExportPageColumns, $fieldType->getExportPageColumns($this, $field));
            }
        }

        return $btExportPageColumns;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getExportFileColumns($blockController, $field)
    {
        $btExportFileColumns = [];
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $field Field */
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            // The "getExportColumns" function isn't there by default, because only a few need to be exported
            if (method_exists($fieldType, 'getExportFileColumns')) {
                $btExportFileColumns = array_merge($btExportFileColumns, $fieldType->getExportFileColumns($this, $field));
            }
        }

        return $btExportFileColumns;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function duplicate($blockController, $field, $variables = [])
    {
        /* @var $db \Doctrine\DBAL\Connection */
        $db = $blockController->getApplication()->make('database')->connection();
        $table = $this->getRepeatableEntriesTable();
        $bID = $blockController->getBlockControllerData()->bID;
        $entries = $this->getRepeatableItems($blockController, $bID);
        foreach ($entries as $entry) {
            unset($entry['id'], $entry['bID']);
            $entry['bID'] = $variables['newBID'];
            $db->insert($table, $entry);
        }
        // TODO once other field types have their own "duplicate" function, call these?
    }
}

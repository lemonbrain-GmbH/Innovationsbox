<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block;

use Concrete\Core\Asset\AssetList;
use Concrete\Core\Block\BlockController as CoreBlockController;
use Concrete\Core\Entity\Block\BlockType\BlockType;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldTypeList;

class BlockController extends CoreBlockController
{
    /**
     * @var string
     */
    protected $btDirectory;

    /**
     * @var array
     */
    protected $blockDeveloperConfig = [];

    /**
     * @var array
     */
    protected $blockDeveloperFields = [];

    /**
     * @var array
     */
    protected $blockDeveloperFieldTypes = [];

    /**
     * @var array
     */
    protected $btExportPageColumns = [];

    /**
     * @var array
     */
    protected $btExportFileColumns = [];

    /**
     * BlockController constructor.
     *
     * @param BlockType $obj |Block $obj
     * @param           $blockDirectory
     * @param string    $configFileName
     *
     * @throws \ReflectionException
     */
    public function __construct($obj = null, $blockDirectory, $configFileName = '/config.php')
    {
        if (!isset($this->app)) {
            // Needed for 5.7.x versions, they don't set this variable unfortunately
            $this->app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
        }
        $this->setBlockTypeDirectory($blockDirectory);
        $this->setBlockDeveloperConfig($configFileName);
        $config = $this->getBlockDeveloperConfig();
        if (is_array($config)) {
            // Block
            if (isset($config['block']) && is_array($config['block'])) {
                if (isset($config['block']['name']) && trim($config['block']['name']) != '') {
                    $this->btName = $config['block']['name'];
                }
                if (isset($config['block']['description']) && trim($config['block']['description']) != '') {
                    $this->btDescription = $config['block']['description'];
                }
                if (isset($config['block']['default_set']) && trim($config['block']['default_set']) != '') {
                    $this->btDefaultSet = $config['block']['default_set'];
                }
            }
            // Cache
            if (isset($config['cache']) && is_array($config['cache'])) {
                if (isset($config['cache']['block_record']) && is_bool($config['cache']['block_record'])) {
                    $this->btCacheBlockRecord = $config['cache']['block_record'];
                }
                if (isset($config['cache']['block_output']) && is_bool($config['cache']['block_output'])) {
                    $this->btCacheBlockOutput = $config['cache']['block_output'];
                }
                if (isset($config['cache']['block_output_lifetime'])
                    && is_numeric($config['cache']['block_output_lifetime'])
                ) {
                    $this->btCacheBlockOutputLifetime = $config['cache']['block_output_lifetime'];
                }
                if (isset($config['cache']['block_output_on_post']) && is_bool($config['cache']['block_output_on_post'])) {
                    $this->btCacheBlockOutputOnPost = $config['cache']['block_output_on_post'];
                }
                if (isset($config['cache']['block_output_for_registered_users'])
                    && is_bool($config['cache']['block_output_for_registered_users'])
                ) {
                    $this->btCacheBlockOutputForRegisteredUsers = $config['cache']['block_output_for_registered_users'];
                }
            }
            // Interface
            if (isset($config['interface']) && is_array($config['interface'])) {
                if (isset($config['interface']['width']) && is_numeric($config['interface']['width'])) {
                    $this->btInterfaceWidth = $config['interface']['width'];
                }
                if (isset($config['interface']['height']) && is_numeric($config['interface']['height'])) {
                    $this->btInterfaceHeight = $config['interface']['height'];
                }
            }
            // Misc
            if (isset($config['misc']) && is_array($config['misc'])) {
                // Grid Framework
                if (isset($config['misc']['ignore_page_theme_grid_framework_container'])
                    && is_bool($config['misc']['ignore_page_theme_grid_framework_container'])
                ) {
                    $this->btIgnorePageThemeGridFrameworkContainer = $config['misc']['ignore_page_theme_grid_framework_container'];
                }
                if (isset($config['misc']['supports_inline_edit']) && is_bool($config['misc']['supports_inline_edit'])) {
                    $this->btSupportsInlineEdit = $config['misc']['supports_inline_edit'];
                }
                if (isset($config['misc']['supports_inline_add']) && is_bool($config['misc']['supports_inline_add'])) {
                    $this->btSupportsInlineAdd = $config['misc']['supports_inline_add'];
                }
            }
            if (trim($this->btTable) == '') {
                if (is_object($obj)) {
                    $this->btTable = 'bt' . (isset($config['misc'], $config['misc']['table_prefix']) && trim($config['misc']['table_prefix']) != '' ? $config['misc']['table_prefix'] : null) . BlockDeveloper::_nameSpace($obj->getBlockTypeHandle());
                } else {
                    $btHandle = end(explode('\\', (new \ReflectionClass($this))->getNamespaceName()));
                    $this->btTable = 'bt' . (isset($config['misc'], $config['misc']['table_prefix']) && trim($config['misc']['table_prefix']) != '' ? $config['misc']['table_prefix'] : null) . $btHandle;
                }
            }
            $dataFields = isset($config['fields']) && is_array($config['fields']) ? $config['fields'] : [];
            $this->blockDeveloperFields = $this->processBlockDeveloperFields($dataFields);
            $this->setExportColumns();
        }

        parent::__construct($obj);
    }

    /* Block Developer specific functions start below */

    /**
     * @return \Concrete\Core\Application\Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * @param string $configFileName
     */
    public function setBlockDeveloperConfig($configFileName = '/config.php')
    {
        $this->blockDeveloperConfig = include $this->getBlockTypeDirectory() . $configFileName;
    }

    /**
     * @return array
     */
    public function getBlockDeveloperConfig()
    {
        return $this->blockDeveloperConfig;
    }

    /**
     * @param $blockDirectory
     */
    public function setBlockTypeDirectory($blockDirectory)
    {
        $this->btDirectory = $blockDirectory;
    }

    /**
     * @return string
     */
    public function getBlockTypeDirectory()
    {
        return $this->btDirectory;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->blockDeveloperFields;
    }

    /**
     * @param $slug
     *
     * @return bool|mixed
     */
    public function getField($slug)
    {
        return isset($this->blockDeveloperFields[$slug]) ? $this->blockDeveloperFields[$slug] : false;
    }

    /**
     * @param $view
     *
     * @return string
     */
    public function renderForm($view)
    {
        $html = null;
        $config = $this->getBlockDeveloperConfig();
        if (isset($config['tabs']) && !empty($config['tabs'])) {
            $randomString = $this->getApplication()->make('helper/validation/identifier')->getString(18);
            $tabs = [];
            $tabsHTML = ['<div class="tab-content">'];
            $tabActive = true;
            $tabFirst = key($config['tabs']);
            foreach ($config['tabs'] as $tabHandle => $tabName) {
                $tabFields = [];
                $tabIdentifier = 'form-' . $tabHandle . '-' . $randomString;
                $tabs[$tabHandle] = [$tabIdentifier, $tabName, $tabActive];
                if ($tabActive === true) {
                    $tabActive = false;
                }
                foreach ($this->getFields() as $fieldSlug => $field) {
                    $fieldTab = $field->getTab();
                    if ($fieldTab == $tabHandle || (!isset($config['tabs'][$fieldTab]) && $tabHandle == $tabFirst)
                        || (trim($fieldTab) == '' && $tabHandle == $tabFirst)
                    ) {
                        $tabFields[] = $field->getFieldType()->renderForm($this, $field, ['view' => $view]);
                    }
                }
                $tabsHTML[] = $this->createTabElement($tabIdentifier, $tabFields, $tabs[$tabHandle][2]);
            }
            $tabsHTML[] = '</div>';
            $html .= $this->getApplication()->make('helper/concrete/ui')->tabs($tabs);
            $html .= implode('', $tabsHTML);
        } else {
            foreach ($this->getFields() as $fieldSlug => $field) {
                $html .= $field->getFieldType()->renderForm($this, $field, ['view' => $view]);
            }
        }

        // TODO move this to field types?.. let's think about it
        return $this->getRenderFormAssets() . $html;
    }

    /**
     * @return string
     */
    public function getRenderFormAssets()
    {
        return '<script type="text/javascript">
    $(function () {
        $.each($("[data-attr-color-picker]"), function(){
            var colorPickerConfig = $.parseJSON($(this).attr("data-attr-color-picker"));
            if ($.isPlainObject(colorPickerConfig)) {
            	$(this).spectrum(colorPickerConfig);
            }
            $(this).removeAttr("data-attr-color-picker");
        });

        $.each($("[data-attr-datetime]"), function(){
	    	var dateTimeConfig = $.parseJSON($(this).attr("data-attr-datetime"));
	        if ($.isPlainObject(dateTimeConfig)) {
	            console.log(dateTimeConfig);
	        	$(this).datetimepicker(dateTimeConfig);
	        }
	        $(this).removeAttr("data-attr-datetime");
	    });
    });
</script>';
    }

    /**
     * @param array       $dataFields
     * @param Field|mixed $fieldRepeatable
     *
     * @return array
     */
    public function processBlockDeveloperFields($dataFields = [], $fieldRepeatable = false)
    {
        $fields = [];
        $fieldTypes = FieldTypeList::getFieldTypes();
        $fieldSlugsBlackList = BlockDeveloper::getFieldSlugsBlackList();
        $viewVariable = $fieldRepeatable ? $fieldRepeatable->getViewVariable() : null;
        if (!empty($dataFields)) {
            foreach ($dataFields as $k => $v) {
                // Check if the FieldType assigned to the Field actually exists/is loaded
                if (isset($fieldTypes[$v['type']])) {
                    $type = $fieldTypes[$v['type']];
                    // Call the constructor of the class with a few variables (in case of needed includes)
                    /* @var $fieldType FieldType */
                    $namespace = $fieldTypes[$v['type']]['namespace'];
                    $fieldType = new $namespace($type['directory'], $type['pkgHandle'], $type['pkgDirectory'], $type['className']);
                    if (!$fieldRepeatable || ($fieldRepeatable && $fieldType->isRepeatable())) {
                        // Only set data for the Field Type if it's set and an array
                        if (isset($v['config']) && is_array($v['config'])) {
                            $fieldType->setConfig($v['config']);
                        }
                        // Only set view for the Field Type if it's set and an array
                        if (isset($v['view']) && is_array($v['view'])) {
                            $fieldType->setViewData($v['view']);
                        }
                        // Some slugs can't be used, since it will cause issues with the SQL database
                        // (like "limit", "select" and so forth)
                        if (in_array($k, $fieldSlugsBlackList)) {
                            $count = 1;
                            $k2 = $k . '_' . $count;
                            // Make sure we haven't already got this in our array for some weird reason
                            while (array_key_exists($k2, $fields)) {
                                ++$count;
                                $k2 = $k . '_' . $count;
                            }
                            $k = $k2;
                        }
                        if ($fieldRepeatable) {
                            $fieldType->setRepeating(true);
                        }
                        $field = new Field($k, $v, $fieldType);
                        if ($fieldRepeatable) {
                            $field->setParent($fieldRepeatable);
                            $field->setViewVariable($viewVariable . '_item[\'' . $k . '\']');
                        }
                        $fields[$k] = $field;
                        $fieldType->constructClass($this, $field);
                    }
                }
            }
        }

        return $fields;
    }

    /* "Default" functions start below */

    /**
     * @return string
     */
    public function getSearchableContent()
    {
        $content = [];
        foreach ($this->getFields() as $fieldSlug => $field) {
            if ($result = $field->getFieldType()->getSearchableContent($this, $field, [])) {
                $content[] = $result;
            }
        }

        return implode(' ', $content);
    }

    public function view()
    {
        $this->set('app', $this->getApplication());
        $variables = ['bID' => $this->bID];
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            // The view function isn't there by default, see "Repeatable" for usage
            if (method_exists($fieldType, 'view')) {
                $fieldType->view($this, $field, $variables);
            }
        }
    }

    public function edit()
    {
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            // The edit function isn't there by default, see "Repeatable" for usage
            if (method_exists($fieldType, 'edit')) {
                $field->getFieldType()->edit($this, $field, []);
            }
        }

        $this->addEdit();
    }

    public function add()
    {
        foreach ($this->getFields() as $fieldSlug => $field) {
            $field->getFieldType()->add($this, $field);
        }

        $this->addEdit();
    }

    public function composer()
    {
        foreach ($this->getFields() as $fieldSlug => $field) {
            $field->getFieldType()->composer($this, $field);
        }

        $this->edit();
    }

    protected function addEdit()
    {
        $al = AssetList::getInstance();
        foreach ($this->getFields() as $fieldSlug => $field) {
            // Pass AssetList (al) as reference
            $field->getFieldType()->addEdit($this, $field, ['al' => &$al]);
        }
    }

    protected function setExportColumns()
    {
        foreach ($this->getFields() as $fieldSlug => $field) {
            /* @var $fieldType FieldType */
            $fieldType = $field->getFieldType();
            // The "getExportColumns" function isn't there by default, because only a few need to be exported
            if (method_exists($fieldType, 'getExportFileColumns')) {
                $this->btExportFileColumns = array_merge($this->btExportFileColumns, $fieldType->getExportFileColumns($this, $field));
            }
            // The "getExportPageColumns" function isn't there by default, because only a few need to be exported
            if (method_exists($fieldType, 'getExportPageColumns')) {
                $this->btExportPageColumns = array_merge($this->btExportPageColumns, $fieldType->getExportPageColumns($this, $field));
            }
        }
    }

    /**
     * @param array $args
     *
     * @return ErrorList
     */
    public function validate($args = [])
    {
        $e = $this->getApplication()->make('helper/validation/error');
        $errors = [];
        foreach ($this->getFields() as $fieldSlug => $field) {
            if ($result = $field->getFieldType()->validate($this, $field, ['args' => $args])) {
                if (is_string($result)) {
                    $errors[] = $result;
                } elseif (is_array($result)) {
                    if (!empty($result)) {
                        foreach ($result as $r) {
                            if (is_string($r) && trim($r) != '') {
                                $errors[] = $r;
                            }
                        }
                    }
                }
            }
        }
        foreach ($errors as $error) {
            $e->add($error);
        }

        return $e;
    }

    /**
     * @param array $args
     */
    public function save($args = [])
    {
        foreach ($this->getFields() as $fieldSlug => $field) {
            // Check if we get a result back
            if ($result = $field->getFieldType()->save($this, $field, ['args' => $args])) {
                // And be sure the "args" key is set and is an array here
                if (is_array($result) && isset($result['args']) && is_array($result['args'])) {
                    $args = $result['args'];
                }
            }
        }

        parent::save($args);
    }

    public function delete()
    {
        foreach ($this->getFields() as $fieldSlug => $field) {
            $field->getFieldType()->delete($this, $field);
        }

        parent::delete();
    }

    /**
     * @param $newBID
     *
     * @return \Concrete\Core\Legacy\BlockRecord
     */
    public function duplicate($newBID)
    {
        $duplicate = parent::duplicate($newBID);

        foreach ($this->getFields() as $fieldSlug => $field) {
            $field->getFieldType()->duplicate($this, $field, ['newBID' => $newBID]);
        }

        return $duplicate;
    }

    private function createTabElement($tabIdentifier, $tabFields, $tabActive)
    {
        $legacy = version_compare(Config::get('concrete.version'), '9.0', '<');
        if ($legacy) {
            $attributes = [
                'class' => 'ccm-tab-content',
                'id'    => 'ccm-tab-content-' . $tabIdentifier,
            ];
        } else {
            $classes = ['tab-pane'];
            if ($tabActive) {
                $classes[] = 'active';
            }
            $attributes = [
                'class' => implode(' ', $classes),
                'id'    => $tabIdentifier,
                'role'  => 'tabpanel',
            ];
        }

        return '<div ' . join(' ', array_map(function ($key) use ($attributes) {
            if (is_bool($attributes[$key])) {
                return $attributes[$key] ? $key : '';
            }
            return $key . '="' . $attributes[$key] . '"';
        }, array_keys($attributes))) . '>' . implode('', $tabFields) . '</div>';
    }
}

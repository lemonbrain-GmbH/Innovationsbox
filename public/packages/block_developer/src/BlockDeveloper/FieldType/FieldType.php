<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;

class FieldType
{
    /**
     * @var string
     */
    protected $ftDirectory;

    /**
     * @var bool
     */
    protected $pkgVersionRequired = false;

    /**
     * @var bool
     */
    protected $appVersionRequired = false;

    /**
     * @var bool
     */
    protected $repeatable = false;

    /**
     * @var bool
     */
    protected $repeating = false;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $viewData = [];

    protected $viewIndent = 0;

    /**
     * FieldType constructor.
     *
     * @param $fieldTypeDirectory
     * @param $pkgHandle
     * @param $pkgDirectory
     * @param $className
     */
    public function __construct($fieldTypeDirectory, $pkgHandle, $pkgDirectory, $className)
    {
        $this->ftDirectory = $fieldTypeDirectory . DIRECTORY_SEPARATOR;
        $this->pkgHandle = $pkgHandle;
        $this->pkgDirectory = $pkgDirectory;
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getFieldTypeName()
    {
        return t($this->className);
    }

    /**
     * @return string
     */
    public function getFieldTypeDescription()
    {
        return null;
    }

    /**
     * @param       $file
     * @param array $variables
     *
     * @return string
     */
    private function incInternal($file, $variables = [])
    {
        if (file_exists($file)) {
            ob_start();
            if (is_array($variables) && !empty($variables)) {
                foreach ($variables as $k => $v) {
                    ${$k} = $v;
                }
            }
            include $file;
            $output = ob_get_contents();
            ob_end_clean();

            return $output;
        }

        return null;
    }

    /**
     * @param        $view
     * @param array  $variables
     * @param string $pkgHandle
     *
     * @return string
     */
    public function inc($view, $variables = [], $pkgHandle = null)
    {
        return $this->incInternal('packages' . DIRECTORY_SEPARATOR . (trim($pkgHandle) == '' ? $this->pkgHandle : $pkgHandle) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'BlockDeveloper' . DIRECTORY_SEPARATOR . 'FieldType' . DIRECTORY_SEPARATOR . $this->className . DIRECTORY_SEPARATOR . $view, $variables);
    }

    /**
     * @return string|bool
     */
    public function getPkgVersionRequired()
    {
        return isset($this->pkgVersionRequired) && $this->pkgVersionRequired !== false ? $this->pkgVersionRequired : false;
    }

    /**
     * @return string|bool
     */
    public function getAppVersionRequired()
    {
        return isset($this->appVersionRequired) && $this->appVersionRequired !== false ? $this->appVersionRequired : false;
    }

    /**
     * @param array $config
     */
    public function setConfig($config = [])
    {
        $this->config = $config;
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * @param array $viewData
     */
    public function setViewData($viewData = [])
    {
        $this->viewData = $viewData;
    }

    /**
     * @param      $key
     * @param null $default
     *
     * @return mixed
     */
    public function getViewData($key, $default = null)
    {
        return isset($this->viewData[$key]) ? $this->viewData[$key] : $default;
    }

    /**
     * @return bool
     */
    public function isRepeatable()
    {
        return $this->repeatable;
    }

    /**
     * @param $repeatable
     */
    public function setRepeatable($repeatable)
    {
        $this->repeatable = $repeatable;
    }

    /**
     * @return bool
     */
    public function isRepeating()
    {
        return $this->repeating;
    }

    /**
     * @param $repeating
     */
    public function setRepeating($repeating)
    {
        $this->repeating = $repeating;
    }

    /**
     * @return string|null
     */
    protected function getPlaceholder()
    {
        $placeholder = $this->getConfig('placeholder');

        return is_string($placeholder) && trim($placeholder) != '' ? $placeholder : null;
    }

    /**
     * @return string|null
     */
    protected function getInputGroupText()
    {
        $inputGroupText = $this->getConfig('input_group_text');

        return is_string($inputGroupText) && trim($inputGroupText) != '' ? $inputGroupText : null;
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
        if ($field->isSearchable()) {
            $fieldSlug = $field->getSlug();
            if (isset($variables[$fieldSlug])) {
                return $variables[$fieldSlug];
            } else {
                return $blockController->get($fieldSlug);
            }
        }

        return null;
    }

    public function setViewIndent()
    {
        if ($viewIndent = $this->getViewData('indent')) {
            if (is_numeric($viewIndent)) {
                $this->viewIndent += $viewIndent;
            }
        } else {
            $this->viewIndent = 0;
        }
    }

    /**
     * @return int
     */
    public function getViewIndent()
    {
        return $this->viewIndent;
    }

    /**
     * @return string|null
     */
    public function getViewIndentHTML()
    {
        $html = null;
        for ($i = 1; $i <= $this->getViewIndent(); ++$i) {
            $html .= '    ';
        }

        return $html;
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
        return $variables;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     *
     * @return bool|string
     */
    public function validate($blockController, $field, $variables = [])
    {
        return false;
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
        return null;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getDatabaseFields($blockController, $field)
    {
        return [];
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return array
     */
    public function getDatabaseTables($blockController, $field)
    {
        return [];
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     *
     * @return string
     */
    public function buildView($blockController, $field)
    {
        return null;
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function add($blockController, $field) {}

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function composer($blockController, $field) {}

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function addEdit($blockController, $field) {}

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function delete($blockController, $field) {}

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function duplicate($blockController, $field, $variables = []) {}

    /**
     * @param BlockController $blockController
     * @param Field           $field
     */
    public function constructClass($blockController, $field) {}
}

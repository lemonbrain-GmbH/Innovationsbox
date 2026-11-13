<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block\Builder;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Config;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class Templates
{
    /**
     * @var string
     */
    protected $assetHandlePattern = '/^([a-z]+)([a-z_]+)[a-z]$/';

    /**
     * @var BlockController
     */
    protected $blockController;

    /**
     * Builder constructor.
     *
     * @param $blockController BlockController
     */
    public function __construct($blockController)
    {
        $this->blockController = $blockController;
    }

    /**
     * @param $handle
     *
     * @return bool
     */
    protected function validHandle($handle)
    {
        preg_match($this->assetHandlePattern, $handle, $matches);

        return $matches ? true : false;
    }

    /**
     * @return bool
     */
    public function build()
    {
        if (!method_exists($this->blockController, 'getBlockDeveloperConfig')) {
            return false;
        }

        $config = $this->blockController->getBlockDeveloperConfig();
        if (isset($config['templates']) && is_array($config['templates'])) {
            $fileService = new FileService();
            $templateDirectory = $this->blockController->getBlockTypeDirectory() . '/templates';
            if (!file_exists($templateDirectory)) {
                $fileService->makeDirectory($templateDirectory);
            }
            $fields = $this->blockController->getFields();
            foreach ($config['templates'] as $templateHandle => $templateValues) {
                if (is_string($templateValues)) {
                    $templateHandle = $templateValues;
                    $templateValues = [];
                }
                if ($this->validHandle($templateHandle)) {
                    $html = '<?php defined("C5_EXECUTE") or die("Access Denied."); ?>' . PHP_EOL . PHP_EOL;
                    $defaults = Config::get($config, 'default.fields.view', []);
                    foreach ($fields as $fieldSlug => $field) {
                        /* @var $field Field */
                        /* @var $fieldType FieldType */
                        $fieldType = $field->getFieldType();
                        $viewData = [];
                        $values = $field->getValues();
                        if (isset($values['templates']) && is_array($values['templates'])
                            && isset($values['templates'][$templateHandle])) {
                            $viewData = $values['templates'][$templateHandle];
                            $field->setPrefix(isset($viewData['prefix']) ? $viewData['prefix'] :
                                (isset($defaults['prefix']) && is_string($defaults['prefix']) ? $defaults['prefix'] : null));
                            $field->setSuffix(isset($viewData['suffix']) ? $viewData['suffix'] :
                                (isset($defaults['suffix']) && is_string($defaults['suffix']) ? $defaults['suffix'] : null));
                        }
                        $fieldType->setViewData($viewData);
                        if (($result = $fieldType->buildView($this->blockController, $field)) && trim($result) != '') {
                            $html .= $result . PHP_EOL;
                        }
                    }
                    if (isset($templateValues['assets']) && is_array($templateValues['assets'])
                        && !empty($templateValues['assets'])) {
                        $templateDirectoryWithAssets = $templateDirectory . '/' . $templateHandle;
                        if (!file_exists($templateDirectoryWithAssets)) {
                            $fileService->makeDirectory($templateDirectoryWithAssets);
                        }
                        $assetService = new AssetService($templateDirectoryWithAssets);
                        $assetService->process($templateValues['assets']);
                        $view = $templateDirectoryWithAssets . '/view.php';
                    } else {
                        $view = $templateDirectory . '/' . $templateHandle . '.php';
                    }
                    $fileService->writeFile($view, $html);
                }
            }
        }

        return true;
    }
}

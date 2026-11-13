<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Block\Builder;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Block\Config;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class View
{
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
     * @return bool
     */
    public function build()
    {
        if (!method_exists($this->blockController, 'getBlockDeveloperConfig')) {
            return false;
        }

        $html = '<?php defined("C5_EXECUTE") or die("Access Denied."); ?>' . PHP_EOL . PHP_EOL;
        $config = $this->blockController->getBlockDeveloperConfig();
        $blockTypeDirectory = $this->blockController->getBlockTypeDirectory();
        $fileService = new FileService();
        $defaults = Config::get($config, 'default.fields.view', []);
        foreach ($this->blockController->getFields() as $fieldSlug => $field) {
            /* @var $field Field */
            /* @var $fieldType FieldType */
            $fieldValues = $field->getValues();
            if (!isset($fieldValues['view'], $fieldValues['view']['prefix']) && isset($defaults['prefix'])
                && is_string($defaults['prefix'])) {
                $field->setPrefix($defaults['prefix']);
            }
            if (!isset($fieldValues['view'], $fieldValues['view']['suffix']) && isset($defaults['suffix'])
                && is_string($defaults['suffix'])) {
                $field->setSuffix($defaults['suffix']);
            }
            $fieldType = $field->getFieldType();
            if (($result = $fieldType->buildView($this->blockController, $field)) && trim($result) != '') {
                $html .= $result . PHP_EOL;
            }
        }
        if (isset($config['assets']) && is_array($config['assets']) && !empty($config['assets'])) {
            $assetService = new AssetService($blockTypeDirectory);
            $assetService->process($config['assets']);
        }

        return $fileService->writeFile($blockTypeDirectory . '/view.php', $html);
    }
}

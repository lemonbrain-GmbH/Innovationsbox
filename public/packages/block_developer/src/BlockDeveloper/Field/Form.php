<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field;

use Config;
use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;

class Form
{
    /**
     * @var Field
     */
    protected $field;

    /**
     * @var BlockController
     */
    protected $blockController;

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var array
     */
    protected $miscFields = [];

    /**
     * @var \Concrete\Core\Form\Service\Form|mixed
     */
    protected $form;

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * Validation constructor.
     *
     * @param Field           $field
     * @param BlockController $blockController
     * @param array           $variables
     * @param array           $miscFields
     */
    public function __construct($field, $blockController, $variables = [], $miscFields = [])
    {
        $this->field = $field;
        $this->blockController = $blockController;
        $this->variables = $variables;
        $this->miscFields = $miscFields;
        $this->app = $blockController->getApplication();
        $this->form = $this->app->make('helper/form');
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return null;
    }

    /**
     * @param       $html
     *
     * @return string
     */
    public function addFormIcon($html)
    {
        if (isset($this->variables['input-group-text'])) {
            if (version_compare(Config::get('concrete.version'), '9.0', '>=')) {
                return sprintf(
                    '<div class="input-group"><div class="input-group-prepend"><span class="input-group-text">%s</span></div>%s</div>',
                    $this->variables['input-group-text'],
                    $html
                );
            } else { // pre v9
                return sprintf(
                    '<div class="input-group"><div class="input-group-addon">%s</div>%s</div>',
                    $this->variables['input-group-text'],
                    $html
                );
            }
        }

        return $html;
    }

    /**
     * @return bool
     */
    public function isRepeating()
    {
        return $this->field->getFieldTypeIsRepeating();
    }

    /**
     * @return \Concrete\Core\Block\View\BlockView
     */
    public function getView()
    {
        return $this->getVariable('view');
    }

    /**
     * @param $key
     * @param $default
     *
     * @return bool|mixed
     */
    public function getVariable($key, $default = null)
    {
        return isset($this->variables[$key]) ? $this->variables[$key] : $default;
    }

    /**
     * @return Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return \Concrete\Core\Form\Service\Form|mixed
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return BlockController
     */
    public function getBlockController()
    {
        return $this->blockController;
    }
}

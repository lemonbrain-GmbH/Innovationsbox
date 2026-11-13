<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\ColorPicker;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class ColorPicker extends FieldType
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
        return t('Color Picker');
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
        $viewVariable = $field->getViewVariable();
        $inner = $viewVariable;

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "") { ?>'
            . $field->getPrefix() . '<?php echo ' . $inner . '; ?>' . $field->getSuffix() . '<?php } ?>';
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
        $fieldSlug = $field->getSlug();
        $variables['args'][$fieldSlug] = isset($variables['args'][$fieldSlug]) && trim($variables['args'][$fieldSlug]) != ''
            ? $variables['args'][$fieldSlug] : '';

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
        $miscFields['config'] = $this->getColorPickerConfig();
        $miscFields = array_merge(['maxlength' => $this->getMaxLength()], $miscFields);

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('colorPicker', $blockController, $variables, $miscFields),
        ]);
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
        return '$.each($(newField).find("[data-attr-color-picker]"), function(){
	            var colorPickerConfig = $.parseJSON($(this).attr("data-attr-color-picker"));
	            if ($.isPlainObject(colorPickerConfig)) {
	                $(this).spectrum(colorPickerConfig); 
	            }
	            $(this).removeAttr("data-attr-color-picker");
	        });';
    }

    /**
     * @param BlockController $blockController
     * @param Field           $field
     * @param array           $variables
     */
    public function addEdit($blockController, $field, $variables = [])
    {
        $blockController->requireAsset('core/colorpicker');
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
                'type' => 'C',
                'size' => $this->getMaxLength(),
            ],
        ];
    }

    /**
     * @return int
     */
    private function getMaxLength()
    {
        return 25;
    }

    /**
     * @return array
     */
    private function getColorPickerConfig()
    {
        $palette = [];
        $defaultPalette = [['#ffffff', '#000000', '#ff0000', '#ff8000', '#ffff00', '#008000', '#0000ff', '#4b0082', '#9400d3']];
        $availableFormats = ['hex', 'hex3', 'hsl', 'rgb', 'name'];
        $preferredFormat = ($pf = $this->getConfig('preferred_format')) && in_array($pf, $availableFormats) ? $pf : false;
        $config = [
            'color' => false,
            'appendTo' => '.ui-dialog',
            'containerClassName' => '',
            'replacerClassName' => '',

            'flat' => (bool) $this->getConfig('flat') === true ? true : false,
            'showInput' => (bool) $this->getConfig('show_input') === true ? true : false,
            'allowEmpty' => (bool) $this->getConfig('allow_empty') === true ? true : false,
            'showButtons' => (bool) $this->getConfig('show_buttons') === true ? true : false,
            'clickoutFiresChange' => (bool) $this->getConfig('clickout_fires_change') === true ? true : false,
            'showInitial' => (bool) $this->getConfig('show_initial') === true ? true : false,
            'showPalette' => (bool) $this->getConfig('show_palette') === true ? true : false,
            'showPaletteOnly' => (bool) $this->getConfig('show_palette_only') === true ? true : false,
            'hideAfterPaletteSelect' => (bool) $this->getConfig('hide_after_palette_select') === true ? true : false,
            'togglePaletteOnly' => (bool) $this->getConfig('toggle_palette_only') === true ? true : false,
            'showSelectionPalette' => (bool) $this->getConfig('show_selection_palette') === true ? true : false,
            'localStorageKey' => (bool) $this->getConfig('local_storage') === true ? true : false,
            'showAlpha' => (bool) $this->getConfig('show_alpha') === true ? true : false,
            'disabled' => (bool) $this->getConfig('disabled') === true ? true : false,
            'preferredFormat' => $preferredFormat,

            'maxSelectionSize' => ($mss = $this->getConfig('max_selection_size')) && is_numeric($mss) ? $mss : 7,
            'cancelText' => ($ct = $this->getConfig('cancel_text')) && trim($ct) != '' ? $ct : t('cancel'),
            'chooseText' => ($cht = $this->getConfig('choose_text')) && trim($cht) != '' ? $cht : t('choose'),
            'togglePaletteMoreText' => ($pmt = $this->getConfig('toggle_palette_more_text')) && trim($pmt) != '' ? $pmt : t('more'),
            'togglePaletteLessText' => ($plt = $this->getConfig('toggle_palette_less_text')) && trim($plt) != '' ? $plt : t('less'),
            'clearText' => ($clt = $this->getConfig('clear_text')) && trim($clt) != '' ? $clt : t('Clear Color Selection'),
            'noColorSelectedText' => ($nct = $this->getConfig('no_color_selected_text')) && trim($nct) != '' ? $nct : t('No Color Selected'),
            'theme' => ($theme = $this->getConfig('theme')) && trim($theme) != '' ? $theme : 'sp-light',
            'selectionPalette' => [],
            'offset' => null,
        ];
        $dataPalette = $this->getConfig('palette', []);
        if (is_array($dataPalette) && !empty($dataPalette)) {
            foreach ($dataPalette as $row => $values) {
                if (is_array($values)) {
                    $values = array_filter($values);
                    $values = array_unique($values);
                    if (!empty($values)) {
                        $values = array_values($values);
                        $palette[] = $values;
                    }
                }
            }
        }
        $config['palette'] = empty($palette) ? $defaultPalette : $palette;

        return $config;
    }
}

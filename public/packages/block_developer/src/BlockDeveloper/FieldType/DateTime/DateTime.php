<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\FieldType\DateTime;

use Devoda\BlockDeveloper\BlockDeveloper\Block\BlockController;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Field;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Validator;
use Devoda\BlockDeveloper\BlockDeveloper\FieldType\FieldType;

class DateTime extends FieldType
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
        return t('Date Time');
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
        $inner = $viewVariable;
        if (($format = $this->getViewData('format')) && trim($format) != '') {
            $inner = '$app->make(\'helper/date\')->formatCustom(\'' . $format . '\', ' . $inner . ')';
        }

        return $this->getViewIndentHTML() . '<?php if (isset(' . $viewVariable . ') && trim(' . $viewVariable . ') != "" && '
            . $viewVariable . ' != "0000-00-00 00:00:00") { ?>' . $field->getPrefix() . '<?php echo ' . $inner . '; ?>'
            . $field->getSuffix() . '<?php } ?>';
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
        $rules[] = 'date_format:' . $this->getDateFormat($this->getDateTimeConfig());

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
        if ($field->isSearchable()) {
            $fieldSlug = $field->getSlug();
            $value = isset($variables[$fieldSlug]) ? $variables[$fieldSlug] : $blockController->get($fieldSlug);
            if (trim($value) != '') {
                if (($format = $this->getViewData('format')) && trim($format) != '') {
                    return $blockController->getApplication()->make('helper/date')->formatCustom($format, $value);
                }

                return $value;
            }
        }

        return null;
    }

    // http://php.net/manual/en/function.date.php
    // http://momentjs.com/docs/#/displaying/format/

    protected function getDateFormat($config = [])
    {
        $dateFormat = isset($config['pickDate']) && $config['pickDate'] === true ? 'm/d/Y' : null;
        if (isset($config['pickTime']) && $config['pickTime'] === true) {
            if (trim($dateFormat) != '') {
                $dateFormat .= ' ';
            }
            $dateFormat .= 'h:i';
            if (isset($config['useSeconds']) && $config['useSeconds'] === true) {
                $dateFormat .= ':s';
            }
            $dateFormat .= ' A';
        }

        return $dateFormat;
    }

    protected function translateDateFormatToJS($dateFormat)
    {
        $search = [
            'Y', 'm', 'd', 'h', 'i', 's',
        ];
        $replace = [
            'YYYY', 'MM', 'DD', 'hh', 'mm', 'ss',
        ];

        return str_replace($search, $replace, $dateFormat);
    }

    /**
     * @param BlockController $blockController
     * @param Field           $fieldRepeatable
     * @param array           $variables
     */
    public function addEdit($blockController, $fieldRepeatable, $variables = [])
    {
        $variables['al']->register('css', 'datetimepicker', 'css/bootstrap-datetimepicker.min.css', [], 'block_developer');
        $variables['al']->register('css', 'bootstrap_fonts', 'css/bootstrap.fonts.css', [], 'block_developer');
        $variables['al']->register('javascript', 'bootstrap', 'js/bootstrap.min.js', [], 'block_developer');
        $variables['al']->register('javascript', 'datetimepicker', 'js/bootstrap-datetimepicker.min.js', [], 'block_developer');

        $blockController->requireAsset('css', 'datetimepicker');
        $blockController->requireAsset('css', 'bootstrap_fonts');
        $blockController->requireAsset('javascript', 'moment');
        $blockController->requireAsset('javascript', 'bootstrap');
        $blockController->requireAsset('javascript', 'datetimepicker');
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
        && trim($variables['args'][$fieldSlug]) != '' ? date('y-m-d H:i:s', strtotime(substr($variables['args'][$fieldSlug], 0, 22))) : '';

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
        $variables['input-group-text'] = ($igt = $this->getInputGroupText()) ? $igt : '<i class="fa fa-calendar"></i>';
        $miscFields['config'] = $this->getDateTimeConfig();
        $miscFields = array_merge(
            [
                'autocomplete' => 'off',
                'placeholder' => $this->getPlaceholder(),
            ],
            $miscFields
        );

        return vsprintf($field->getFormTemplate(), [
            $field->generateFormContent('label', $blockController, $variables),
            $field->generateFormContent('dateTime', $blockController, $variables, $miscFields),
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
        return '$.each($(newField).find("[data-attr-datetime]"), function(){
	            var dateTimeConfig = $.parseJSON($(this).attr("data-attr-datetime"));
	            if ($.isPlainObject(dateTimeConfig)) {
	                $(this).datetimepicker(dateTimeConfig); 
	            }
	            $(this).removeAttr("data-attr-datetime");
	        });';
    }

    /**
     * @return array
     */
    protected function getDateTimeConfig()
    {
        $config = [
            'pickDate' => (bool) $this->getConfig('pick_date') === true ? true : false,
            'pickTime' => (bool) $this->getConfig('pick_time') === true ? true : false,
            'useMinutes' => (bool) $this->getConfig('use_minutes') === true ? true : false,
            'useSeconds' => (bool) $this->getConfig('use_seconds') === true ? true : false,
            'useCurrent' => (bool) $this->getConfig('use_current') === true ? true : false,
            'showToday' => (bool) $this->getConfig('show_today') === true ? true : false,
            'useStrict' => (bool) $this->getConfig('use_strict') === true ? true : false,
            'sideBySide' => (bool) $this->getConfig('side_by_side') === true ? true : false,

            'minuteStepping' => ($ms = $this->getConfig('minute_stepping')) && is_numeric($ms) && $ms > 1 && $ms < 30 ? $ms : 1,
            'minDate' => ($md = $this->getConfig('min_date')) && trim($md) != '' ? $md : '1/1/1900',
            'maxDate' => ($mxd = $this->getConfig('max_date')) && trim($mxd) != '' ? $mxd : null,
            'defaultDate' => ($dd = $this->getConfig('default_date')) && trim($dd) != '' ? $dd : '',

            'icons ' => [
                'time' => ($it = $this->getConfig('icon_time')) && trim($it) != '' ? $it : 'glyphicon glyphicon-time',
                'date' => ($id = $this->getConfig('icon_date')) && trim($id) != '' ? $id : 'glyphicon glyphicon-calendar',
                'up' => ($iu = $this->getConfig('icon_up')) && trim($iu) != '' ? $iu : 'glyphicon glyphicon-chevron-up',
                'down' => ($idw = $this->getConfig('icon_down')) && trim($idw) != '' ? $idw : 'glyphicon glyphicon-chevron-down',
            ],
            'language' => ($lg = $this->getConfig('language')) && trim($lg) != '' ? $lg : 'en',
            'disabledDates' => ($dsd = $this->getConfig('disabled_dates')) && is_array($dsd) ? $dsd : [],
            'enabledDates' => ($ed = $this->getConfig('enabled_dates')) && is_array($ed) ? $ed : [],
            'daysOfWeekDisabled' => ($dwd = $this->getConfig('days_of_week_disabled_dates')) && is_array($dwd) ? $dwd : [],
        ];
        if ($config['pickDate'] !== true && $config['pickTime'] !== true) {
            $config['pickDate'] = true;
        }
        if (trim($config['maxDate']) == '') {
            unset($config['maxDate']);
        }
        if ($config['pickTime'] !== true) {
            $config['useMinutes'] = false;
            $config['useSeconds'] = false;
            $config['sideBySide'] = false;
        }
        $config['format'] = $this->translateDateFormatToJS($this->getDateFormat($config));

        return $config;
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
                'type' => 'T',
            ],
        ];
    }
}

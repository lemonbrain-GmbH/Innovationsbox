<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class DateTime extends Form
{
    /**
     * @return string
     */
    public function getHtml()
    {
        $config = [];
        $field = $this->getField();
        $slug = $field->getSlug();
        if ($this->isRepeating()) {
            $fieldParent = $field->getParent();
            $baseAttributes = [
                'name' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'id' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'class' => 'form-control',
            ];
            $baseAttributes['type'] = 'text';
            if (isset($this->miscFields['config'])) {
                $config = $this->miscFields['config'];
                unset($this->miscFields['config']);
                $jsonArray = json_encode($config);
                $this->miscFields['data-attr-datetime'] = htmlspecialchars($jsonArray);
            }
            $dateFormat = $this->getDateFormat($config);
            $dateFormat = $this->translateDateFormatToJS($dateFormat);
            $value = '{{formatDate ' . $slug . ' \'' . $dateFormat . '\'}}';
            $baseAttributes['value'] = isset($this->miscFields['value']) ? $this->miscFields['value'] : $value;
            $attributes = array_merge($baseAttributes, $this->miscFields);
            $dateTime = '<input ' . join(' ', array_map(function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key] ? $key : '';
                }

                return $key . '="' . $attributes[$key] . '"';
            }, array_keys($attributes))) . ' />';
        } else {
            if (isset($this->miscFields['config'])) {
                $config = $this->miscFields['config'];
                unset($this->miscFields['config']);
                $jsonArray = json_encode($config);
                $this->miscFields['data-attr-datetime'] = htmlspecialchars($jsonArray);
            }
            $value = isset($this->miscFields['value']) ? $this->miscFields['value'] : $this->blockController->get($slug);
            $dateFormat = $this->getDateFormat($config);
            $value = trim($value) != '' && $value != '0000-00-00 00:00:00' ? date($dateFormat, strtotime($value)) : null;
            $view = $this->getView();
            $form = $this->getForm();
            $dateTime = $form->text($view->field($slug), $value, $this->miscFields);
        }

        return $this->addFormIcon($dateTime);
    }

    /**
     * @param array $config
     *
     * @return string|null
     */
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

    /**
     * @param $dateFormat
     *
     * @return mixed
     */
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
}

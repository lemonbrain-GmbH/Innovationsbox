<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class ColorPicker extends Form
{
    /**
     * @return string
     */
    public function getHtml()
    {
        $field = $this->getField();
        $slug = $field->getSlug();
        if ($this->isRepeating()) {
            $fieldParent = $field->getParent();
            $baseAttributes = [
                'name' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'id' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'class' => 'form-control',
            ];
            $value = !isset($this->variables['escape']) || $this->variables['escape'] === true
                ? '{{ ' . $slug . ' }}' : '{{{ ' . $slug . ' }}}';
            $baseAttributes['type'] = 'text';
            $baseAttributes['value'] = isset($this->miscFields['value']) ? $this->miscFields['value'] : $value;
            if (isset($this->miscFields['config'])) {
                $config = $this->miscFields['config'];
                unset($this->miscFields['config']);
                $jsonArray = json_encode($config);
                $this->miscFields['data-attr-color-picker'] = htmlspecialchars($jsonArray);
            }
            $attributes = array_merge($baseAttributes, $this->miscFields);
            $colorPicker = '<input ' . join(' ', array_map(function ($key) use ($attributes) {
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
                $this->miscFields['data-attr-color-picker'] = htmlspecialchars($jsonArray);
            }
            $value = isset($this->miscFields['value']) ? $this->miscFields['value'] : $this->blockController->get($slug);
            $view = $this->getView();
            $form = $this->getForm();
            $colorPicker = $form->text($view->field($slug), $value, $this->miscFields);
        }

        return $this->addFormIcon('<div></div>' . $colorPicker);
    }
}

<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class SelectIcon extends Form
{
    /**
     * @return string
     */
    public function getHtml()
    {
        $field = $this->getField();
        $slug = $field->getSlug();
        $optionValues = [];
        if (isset($this->variables['optionValues']) && is_array($this->variables['optionValues'])) {
            $optionValues = $this->variables['optionValues'];
        }
        if ($this->isRepeating()) {
            $fieldParent = $field->getParent();
            $baseAttributes = [
                'name'  => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'id'    => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'class' => 'form-control',
            ];
            $options = [];
            foreach ($optionValues as $k => $v) {
                $options[] = sprintf('<option value="%s">%s</option>', $k, $v);
            }
            $attributes = array_merge($baseAttributes, $this->miscFields);
            $select = '<select ' . join(' ', array_map(function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key] ? $key : '';
                }
                return $key . '="' . $attributes[$key] . '"';
            }, array_keys($attributes))) . '>{{#select ' . $slug . '}}' . implode('', $options) . '{{/select}}</select>';
        } else {
            $view = $this->getView();
            $form = $this->getForm();
            $value = isset($this->miscFields['value']) ? $this->miscFields['value'] : $this->blockController->get($slug);
            $select = $form->select($view->field($slug), $optionValues, $value, $this->miscFields);
        }

        $iconDiv = '<div><i id="selected-icon"></i></div>';

        return $this->addFormIcon($select . $iconDiv);
    }
}

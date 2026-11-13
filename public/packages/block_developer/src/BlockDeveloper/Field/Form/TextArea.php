<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class TextArea extends Form
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
            $value = !isset($this->variables['escape']) || $this->variables['escape'] === true ?
                '{{ ' . $slug . ' }}' : '{{{ ' . $slug . ' }}}';
            $baseAttributes = [
                'name' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'id' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'class' => 'form-control',
            ];
            $attributes = array_merge($baseAttributes, $this->miscFields);
            $textArea = '<textarea ' . join(' ', array_map(function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key] ? $key : '';
                }

                return $key . '="' . $attributes[$key] . '"';
            }, array_keys($attributes))) . '>' . $value . '</textarea>';
        } else {
            $view = $this->getView();
            $form = $this->getForm();
            $value = isset($this->miscFields['value']) ? $this->miscFields['value'] : $this->blockController->get($slug);
            $textArea = $form->textarea($view->field($slug), $value, $this->miscFields);
        }

        return $this->addFormIcon($textArea);
    }
}

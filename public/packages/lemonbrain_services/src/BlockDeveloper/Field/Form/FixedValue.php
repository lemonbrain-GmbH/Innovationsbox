<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class FixedValue extends Form
{
    /**
     * @return string
     */
    public function getHtml()
    {
        $field = $this->getField();
        $slug = $field->getSlug();
        $view = $this->getView();
        $form = $this->getForm();

        $valueFunction = $field->getValues()['fixedValue'];
        $value = $valueFunction->call($this);
        if ($field->getValues()['hidden'] ? true : false) {
            $formControl = $form->hidden($view->field($slug), $value, $this->miscFields);
        } else {
            $formControl = $form->text($view->field($slug), $value, $this->miscFields);
        }

        return $this->addFormIcon($formControl);
    }
}

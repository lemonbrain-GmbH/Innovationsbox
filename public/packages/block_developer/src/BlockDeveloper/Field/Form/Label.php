<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class Label extends Form
{
    /**
     * @var string
     */
    protected $descriptionTemplate = '<i class="fa fa-question-circle launch-tooltip" data-bs-original-title="%1$s" data-original-title="%1$s"></i> ';

    /**
     * @return string
     */
    public function getHtml()
    {
        $field = $this->getField();
        $slug = $field->getSlug();
        $description = $field->getDescription();
        $required = $field->isRequired() ? sprintf(' <small class="required">%s</small>', t('Required')) : null;
        $labelInner = trim($description) != '' ? sprintf($this->descriptionTemplate, $description) : null;
        $labelInner .= $field->getLabel();
        if ($this->isRepeating()) {
            return sprintf(
                '<label for="%s[{{id}}][%s]" class="control-label">%s</label>%s',
                $field->getParent()->getSlug(),
                $slug,
                $labelInner,
                $required
            );
        }

        $view = $this->getView();
        $form = $this->getForm();

        return $form->label($view->field($slug), $labelInner) . $required;
    }
}

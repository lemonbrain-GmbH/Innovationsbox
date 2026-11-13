<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class SelectMultiple extends Form
{
    /**
     * @return string
     */
    public function getHtml()
    {
        // @TODO build repeatable support, once needed
        if ($this->isRepeating()) {
            return null;
        }

        $slug = $this->getField()->getSlug();
        $view = $this->getView();
        $form = $this->getForm();
        $optionValues = isset($this->miscFields['optionValues']) ? $this->miscFields['optionValues'] : [];
        $defaultValues = isset($this->miscFields['defaultValues']) ? $this->miscFields['defaultValues'] : [];
        $mf = array_filter($this->miscFields, function ($item) {
            if ($item === 'optionValues' || $item === 'defaultValues') {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_KEY);
        $selectMultiple = $form->selectMultiple($view->field($slug), $optionValues, $defaultValues, $mf);
        $selectMultiple .= '
            <script>
                $(document).ready(function() {
                    new TomSelect("select[multiple]#' . $slug . '", {
                        plugins: {
                            remove_button: {
                                title:"Remove this item",
                            }
                        }
                    }); 
                });
            </script>';
        return $this->addFormIcon($selectMultiple);
    }
}

<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class Page extends Form
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
            $legacy = version_compare(Config::get('concrete.version'), '9.0', '<');
            $baseAttributes = [
                'id' => $fieldParent->getSlug() . '-' . $slug . '-page-{{id}}',
            ];
            // Versions below 9.x have the "concretePageSelector" available
            if ($legacy) {
                $baseAttributes = [
                    'data-page-selector-input-name' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                    'data-page-selector-page-id' => '{{ ' . $slug . ' }}',
                    'class' => 'ccm-page-selector',
                ];
            }

            $attributes = array_merge($baseAttributes, $this->miscFields);
            $page = '<div ' . join(' ', array_map(function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key] ? $key : '';
                }
                return $key . '="' . $attributes[$key] . '"';
            }, array_keys($attributes))) . '>
<concrete-page-input page-id="{{ ' . $slug . ' }}" 
                     choose-text="' . t('Choose Page') . '" 
                     input-name="' . $fieldParent->getSlug() . '[{{id}}][' . $slug . ']' . '"
                     include-system-pages="false" 
                     ask-include-system-pages="false">
</concrete-page-input>
</div>';

            if ($legacy) {
                $page = '<div ' . join(' ', array_map(function ($key) use ($attributes) {
                    if (is_bool($attributes[$key])) {
                        return $attributes[$key] ? $key : '';
                    }

                    return $key . '="' . $attributes[$key] . '"';
                }, array_keys($attributes))) . '></div>';
            }

        } else {
            $view = $this->getView();
            $pageSelector = $this->app->make('helper/form/page_selector');
            $value = isset($this->miscFields['value']) ? $this->miscFields['value'] : $this->blockController->get($slug);
            $page = $pageSelector->selectPage($view->field($slug), $value);
        }

        return $this->addFormIcon($page);
    }
}

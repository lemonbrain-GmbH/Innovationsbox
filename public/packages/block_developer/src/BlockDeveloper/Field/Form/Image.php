<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Concrete\Core\Support\Facade\Config;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class Image extends Form
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
                'id' => $fieldParent->getSlug() . '-' . $slug . '-image-{{id}}',
            ];
            // Versions below 9.x have the "concreteFileSelector" available
            if ($legacy) {
                $baseAttributes = [
                    'data-file-selector-input-name' => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                    'data-file-selector-f-id' => '{{ ' . $slug . ' }}',
                    'class' => 'ccm-file-selector',
                ];
            }
            $attributes = array_merge($baseAttributes, $this->miscFields);
            $image = '<div ' . join(' ', array_map(function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key] ? $key : '';
                }
                return $key . '="' . $attributes[$key] . '"';
            }, array_keys($attributes))) . '>
<concrete-file-input file-id="{{ ' . $slug . ' }}" 
                     choose-text="' . t('Choose Image') . '" 
                     input-name="' . $fieldParent->getSlug() . '[{{id}}][' . $slug . ']' . '">
</concrete-file-input>
</div>';

            if ($legacy) {
                $image = '<div ' . join(' ', array_map(function ($key) use ($attributes) {
                    if (is_bool($attributes[$key])) {
                        return $attributes[$key] ? $key : '';
                    }

                    return $key . '="' . $attributes[$key] . '"';
                }, array_keys($attributes))) . '></div>';
            }
        } else {
            $string = $this->app->make('helper/validation/identifier')->getString(18);
            $view = $this->getView();
            $al = $this->app->make('helper/concrete/asset_library');
            $id = $slug . '-' . $string;
            $chooseText = $field->getFieldType()->getConfig('choose_text');
            $chooseText = is_string($chooseText) && trim($chooseText) != '' ? $chooseText : t('Choose Image');
            $image = $al->image($id, $view->field($slug), $chooseText, $this->getVariable('fileInstanceBlock'), $this->miscFields);
        }

        return $this->addFormIcon($image);
    }
}

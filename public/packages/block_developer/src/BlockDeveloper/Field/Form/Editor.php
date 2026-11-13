<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Form;

class Editor extends Form
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
                'name'  => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'id'    => $fieldParent->getSlug() . '[{{id}}][' . $slug . ']',
                'class' => 'form-control',
            ];
            $attributes = !empty($this->miscFields) ? array_merge($baseAttributes, $this->miscFields) : $baseAttributes;

            return '<textarea ' . join(' ', array_map(function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key] ? $key : '';
                }

                return $key . '="' . $attributes[$key] . '"';
            }, array_keys($attributes))) . '>{{ ' . $slug . ' }}</textarea>';
        }

        $view = $this->getView();
        $editor = $this->app->make('editor');
        $config = $this->app->make('site')->getDefault()->getConfigRepository();
        $pluginManager = $editor->getPluginManager();
        $pluginManager->select($config->get('editor.ckeditor4.plugins.selected_default'));
        if (isset($this->miscFields['include_plugins']) && is_array($this->miscFields['include_plugins']) && !empty($this->miscFields['include_plugins'])) {
            $pluginManager->select($this->miscFields['include_plugins']);
        }
        if (isset($this->miscFields['exclude_plugins']) && is_array($this->miscFields['exclude_plugins']) && !empty($this->miscFields['exclude_plugins'])) {
            $pluginManager->deselect($this->miscFields['exclude_plugins']);
        }

        $value = isset($this->miscFields['value']) ? $this->miscFields['value'] : $this->blockController->get($slug);

        return $editor->outputBlockEditModeEditor($view->field($slug), $value);
    }
}

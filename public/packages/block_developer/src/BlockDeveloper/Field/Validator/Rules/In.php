<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class In extends Rule
{
    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        $values = $this->getValues();
        if (isset($data[$fieldSlug]) && trim($data[$fieldSlug]) != '' && !in_array($data[$fieldSlug], $values)) {
            return $this->getMessage();
        }

        return true;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        if ($this->isRepeating()) {
            $fieldParent = $this->getParentField();

            return t(
                'The %s field has an invalid value (%s, item #%s).',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field has an invalid value.', $this->getField()->getLabel());
    }
}

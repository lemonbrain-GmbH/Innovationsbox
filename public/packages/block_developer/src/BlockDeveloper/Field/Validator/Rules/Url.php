<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class Url extends Rule
{
    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        if (isset($data[$fieldSlug]) && is_string($data[$fieldSlug]) && trim($data[$fieldSlug]) != ''
            && !filter_var($data[$fieldSlug], FILTER_VALIDATE_URL)
        ) {
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
                'The %s field is an invalid URL (%s, item #%s).',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field is an invalid URL.', $this->getField()->getLabel());
    }
}

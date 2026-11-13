<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Concrete\Core\Page\Page as CorePage;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class Page extends Rule
{
    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        $values = $this->getValues();
        if (isset($data[$fieldSlug]) && is_string($data[$fieldSlug]) && trim($data[$fieldSlug]) != '') {
            // If required, always check - otherwise just if the value exceeds 0 (clearing the page selector gives 0)
            if (in_array('required', $values) || $data[$fieldSlug] > 0) {
                if (!($f = CorePage::getByID($data[$fieldSlug])) || !is_object($f) || $f->isError() || $f->isInTrash()) {
                    return $this->getMessage();
                }
            }
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
                'The %s field is an invalid page (%s, item #%s).',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field is an invalid page.', $this->getField()->getLabel());
    }
}

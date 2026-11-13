<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Concrete\Core\File\File as CoreFile;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class File extends Rule
{
    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        if (isset($data[$fieldSlug]) && is_string($data[$fieldSlug]) && trim($data[$fieldSlug]) != '') {
            if ($data[$fieldSlug] == '0' && !$this->getField()->isRequired()) {
                return true;
            }
            if (!($f = CoreFile::getByID($data[$fieldSlug])) || !is_object($f)) {
                return $this->getMessage();
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
                'The %s field is an invalid file (%s, item #%s).',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field is an invalid file.', $this->getField()->getLabel());
    }
}

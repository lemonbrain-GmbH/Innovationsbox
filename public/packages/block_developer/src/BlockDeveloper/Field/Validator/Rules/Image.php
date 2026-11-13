<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Concrete\Core\File\File as CoreFile;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class Image extends Rule
{
    protected $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'bmp', 'tiff', 'webp'];

    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        if (isset($data[$fieldSlug]) && is_string($data[$fieldSlug]) && trim($data[$fieldSlug]) != ''
            && ($f = CoreFile::getByID($data[$fieldSlug])) && is_object($f)
        ) {
            /* @var $f \Concrete\Core\Entity\File\Version */
            /* @var $type \Concrete\Core\File\Type\Type */
            if (!($type = $f->getTypeObject()) || !is_object($type)
                || !in_array(strtolower($type->getExtension()), $this->imageExtensions)) {
                return $type->getExtension();
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
                'The %s field is an invalid image (%s, item #%s).',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field is an invalid image.', $this->getField()->getLabel());
    }
}

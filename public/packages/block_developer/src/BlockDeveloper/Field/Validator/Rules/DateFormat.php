<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use DateTime;
use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class DateFormat extends Rule
{
    /**
     * @var string
     */
    protected $format = '';

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        if (isset($data[$fieldSlug]) && trim($data[$fieldSlug]) != '') {
            $values = $this->getValues();
            $this->setFormat(isset($values[0]) && trim($values[0]) != '' ? $values[0] : 'Y-m-d H:i:s');

            if (!$this->validateDate($data[$fieldSlug], $this->getFormat())) {
                return $this->getMessage();
            }
        }

        return true;
    }

    /**
     * @param $date
     * @param $format
     *
     * @return bool
     */
    protected function validateDate($date, $format)
    {
        return ($d = DateTime::createFromFormat($format, $date)) && $d->format($format) == $date;
    }

    /**
     * @return string
     */

    /**
     * @return string
     */
    public function getMessage()
    {
        if ($this->isRepeating()) {
            $fieldParent = $this->getParentField();

            return t(
                'The %s field has an invalid date format (%s, item #%s) - %s.',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow(),
                $this->getFormat()
            );
        }

        return t('The %s field has an invalid date format - %s.', $this->getField()->getLabel(), $this->getFormat());
    }
}

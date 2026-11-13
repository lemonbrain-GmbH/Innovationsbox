<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class ArrayIn extends Rule
{
    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        if (isset($data[$fieldSlug]) && is_array($data[$fieldSlug]) && !empty($data[$fieldSlug])) {
            $values = $this->getValues();
            if (!empty($values)) {
                $amount = 0;
                foreach ($data[$fieldSlug] as $k) {
                    if (!in_array($k, $values)) {
                        ++$amount;
                    }
                }
                if ($amount > 0) {
                    return $this->getMessage($amount);
                }
            }
        }

        return true;
    }

    /**
     * @param int $amount
     *
     * @return string
     */
    public function getMessage($amount = 0)
    {
        if ($this->isRepeating()) {
            $fieldParent = $this->getParentField();

            return t(
                'The %s field has %s invalid value(s) - (%s, item #%s).',
                $this->getField()->getLabel(),
                $amount,
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field has %s invalid value(s).', $this->getField()->getLabel(), $amount);
    }
}

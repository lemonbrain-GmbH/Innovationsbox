<?php

declare(strict_types=1);

namespace Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rules;

use Devoda\BlockDeveloper\BlockDeveloper\Field\Validator\Rule;

class Email extends Rule
{
    /**
     * @return bool|string
     */
    public function validate()
    {
        $data = $this->getData();
        $fieldSlug = $this->getSlug();
        if (isset($data[$fieldSlug]) && is_string($data[$fieldSlug]) && trim($data[$fieldSlug]) != ''
            && !$this->isValidEmail($data[$fieldSlug])
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
                'The %s field is an invalid email address (%s, item #%s).',
                $this->getField()->getLabel(),
                $fieldParent->getLabel(),
                $this->getRow()
            );
        }

        return t('The %s field is an invalid email address.', $this->getField()->getLabel());
    }

    /**
     * @param $email
     *
     * @return bool
     */
    protected function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email)
            && !preg_match('/@\[/', $email) && !preg_match('/".+@/', $email) && !preg_match('/=.+@/', $email);
    }
}

<?php

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Phone number constraint.
 */
class PhoneNumberConstraint extends Constraint
{
    public $message;

    public function getMessage()
    {
        if ($this->message !== null) {
            return $this->message;
        }
    }
}

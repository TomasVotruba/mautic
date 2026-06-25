<?php declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

class MultipleEmailsValid extends Constraint
{
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}

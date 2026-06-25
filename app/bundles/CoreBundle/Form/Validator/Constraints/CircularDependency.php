<?php declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class CircularDependency extends Constraint
{
    public $message;

    public function validatedBy(): string
    {
        return CircularDependencyValidator::class;
    }
}

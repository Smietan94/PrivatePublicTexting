<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var Password $constraint */
        if (!preg_match('/[^a-zA-Z0-9]/', $value)) {
            // TODO: implement the validation here
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

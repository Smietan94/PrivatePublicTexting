<?php

declare(strict_types=1);

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUserNameValidator extends ConstraintValidator
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        /* @var App\Validator\UniqueUserName $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        // TODO: implement the validation here
        $user = $this->userRepository->findOneBy(['username' => $value]);

        if ($user) {
            $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
        }
    }
}

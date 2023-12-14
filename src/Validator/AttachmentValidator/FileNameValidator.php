<?php

namespace App\Validator\AttachmentValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FileNameValidator extends ConstraintValidator
{
    /**
     * validate
     *
     * @param  UploadedFile[] $files
     * @param  Constraint $constraint
     * @return void
     */
    public function validate($files, Constraint $constraint)
    {
        /* @var App\Validator\FileName $constraint */

        if (null === $files || '' === $files) {
            return;
        }

        foreach ($files as $file) {
            if (!preg_match('/^[a-zA-Z0-9\s_-]+.[a-zA-Z0-9]+$/', $file->getClientOriginalName())) {
                $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $file->getClientOriginalName())
                ->addViolation();
            }
        }
    }
}

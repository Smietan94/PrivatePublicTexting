<?php

declare(strict_types=1);

namespace App\Validator\AttachmentValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UploadSizeValidator extends ConstraintValidator
{
    /**
     * validate
     *
     * @param  UploadedFile[] $files
     * @param  Constraint     $constraint
     * @return void
     */
    public function validate($files, Constraint $constraint)
    {
        /* @var App\Validator\FileSize $constraint */
        $uploadSize    = 0;
        $maxUploadSize = 20 * 1024 * 1024;

        if (null === $files || '' === $files) {
            return;
        }

        foreach ($files as $file) {
            $uploadSize += $file->getSize();
        }

        if ($uploadSize > $maxUploadSize) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

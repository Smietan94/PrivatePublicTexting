<?php

declare(strict_types=1);

namespace App\Validator\AttachmentValidator;

use App\Entity\Constants\Constant;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxFileUploadsValidator extends ConstraintValidator
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
        /* @var App\Validator\MaxFileUploads $constraint */

        if (null === $files || '' === $files) {
            return;
        }

        if (count($files) > Constant::MAX_FILE_UPLOADS) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

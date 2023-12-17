<?php

namespace App\Validator\AttachmentValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AttachmentTypeValidator extends ConstraintValidator
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
        /* @var App\Validator\AttachmentValidator\AttachmentType $constraint */

        if (null === $files || '' === $files) {
            return;
        }

        $fileTypes = [
            'image/jpeg',
            'image/png',
            'text/plain',
            'application/pdf',
        ];

        foreach ($files as $file) {
            // dd($file);
            if (!in_array($file->getClientMimeType(), $fileTypes)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $file->getClientOriginalName())
                    ->addViolation();
            }
        }

        // TODO: implement the validation here
        // $this->context->buildViolation($constraint->message)
        //     ->setParameter('{{ value }}', $files)
        //     ->addViolation();
    }
}

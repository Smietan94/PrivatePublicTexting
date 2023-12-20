<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FileExtensionValidator extends ConstraintValidator
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
        /* @var App\Validator\FileExtension $constraint */
        if (null === $files || '' === $files) {
            return;
        }

        $allowedExtendsions = [
            'jpg',
            'jpeg',
            'png',
            'txt',
            'pdf'
        ];

        foreach ($files as $file) {
            if (!in_array($file->getClientOriginalExtension(), $allowedExtendsions)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $file->getClientOriginalName())
                    ->addViolation();
            }
        }
    }
}

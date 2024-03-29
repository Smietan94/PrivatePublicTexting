<?php

declare(strict_types=1);

namespace App\Validator\AttachmentValidator;

use App\Entity\Constants\Constant;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MaxFileUploads extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'Too much files, max file uploads = {{ Constant::MAX_FILE_UPLOADS }}';
}

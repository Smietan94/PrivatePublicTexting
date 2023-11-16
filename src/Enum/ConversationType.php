<?php

declare(strict_types=1);

namespace App\Enum; 

enum ConversationType: int
{
    case SOLO  = 0;
    case GROUP = 1;

    public function toString()
    {
        return match($this) {
            self::GROUP => 'group',
            default     => 'solo'
        };
    }

    public function toInt()
    {
        return match($this) {
            self::GROUP => 1,
            default     => 0
        };
    }
}
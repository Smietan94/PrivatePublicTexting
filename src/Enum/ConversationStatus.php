<?php

declare(strict_types=1);

namespace App\Enum; 

enum ConversationStatus: int
{
    case DELETED = 0;
    case ACTIVE  = 1;

    /**
     * toString
     *
     * @return string
     */
    public function toString()
    {
        return match($this) {
            self::DELETED => 'deleted',
            default       => 'active'
        };
    }

    /**
     * toInt
     *
     * @return int
     */
    public function toInt()
    {
        return match($this) {
            self::DELETED => 0,
            default       => 1
        };
    }
}
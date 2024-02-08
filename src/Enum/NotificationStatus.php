<?php

declare(strict_types=1);

namespace App\Enum; 

enum NotificationStatus: int
{
    case UNSEEN    = 0;
    case DISPLAYED = 1;

    /**
     * 
     * toString
     *
     * @return string
     */
    public function toString()
    {
        return match($this) {
            self::DISPLAYED => 'displayed',
            default         => 'unseen'
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
            self::DISPLAYED => 1,
            default         => 0
        };
    }

    /**
     * toBool
     * 
     * @return bool
     */
    public function toBool()
    {
        return match($this) {
            self::DISPLAYED => true,
            default         => false
        };
    }
}
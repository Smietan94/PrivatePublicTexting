<?php

declare(strict_types=1);

namespace App\Enum;

enum UserSatatus: int
{
    case ACTIVE    = 0;
    case LOGGEDOUT = 1;
    case INACTIVE  = 2;
    case SUSPENDED = 3;
    case BANNED    = 4;

    public function toString()
    {
        return match($this) {
            self::LOGGEDOUT => 'logged out',
            self::INACTIVE  => 'inactive',
            self::SUSPENDED => 'suspended',
            self::BANNED    => 'banned',
            default         => 'active'
        };
    }

    public function toInt()
    {
        return match($this) {
            self::LOGGEDOUT => 1,
            self::INACTIVE  => 2,
            self::SUSPENDED => 3,
            self::BANNED    => 4,
            default         => 0
        };
    }
}
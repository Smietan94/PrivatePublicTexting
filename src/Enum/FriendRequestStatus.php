<?php

declare(strict_types=1);

namespace App\Enum;

enum FriendRequestStatus: int
{
    case PENDING   = 0;
    case ACCEPTED  = 1;
    case REJECTED  = 2;
    case CANCELLED = 3;
    case EXPIRED   = 4;

    public function toString()
    {
        return match($this) {
            self::ACCEPTED  => 'accepted',
            self::REJECTED  => 'rejected',
            self::CANCELLED => 'cancelled',
            self::EXPIRED   => 'expired',
            default         => 'pending'
        };
    }

    public function toInt()
    {
        return match($this) {
            self::ACCEPTED  => 1,
            self::REJECTED  => 2,
            self::CANCELLED => 3,
            self::EXPIRED   => 4,
            default         => 0
        };
    }
}
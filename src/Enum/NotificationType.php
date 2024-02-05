<?php

declare(strict_types=1);

namespace App\Enum; 

enum NotificationType: int
{
    case CONVERSATION_GROUP_CREATED = 0;
    case FRIEND_REQUEST_RECEIVED    = 1;
    case FRIEND_REQUEST_DENIED      = 2;
    case FRIEND_REQUEST_ACCEPTED    = 3;
    case REMOVED_FROM_FRIENDS_LIST  = 4;
    case REMOVED_FROM_CONVERSATION  = 5;
    case LEFT_THE_CONVERSATION      = 6;
    case REMOVED_CONVERSATION       = 7;
    case ADDED_TO_CONVERSATION      = 8;
    case CONVERSATION_NAME_CHANGED  = 9;

    /**
     * toString
     *
     * @return string
     */
    public function toString()
    {
        return match($this) {
            self::FRIEND_REQUEST_RECEIVED   => 'friend request received from %s',
            self::FRIEND_REQUEST_DENIED     => 'friend request denied by %s',
            self::FRIEND_REQUEST_ACCEPTED   => 'friend request accepted by %s',
            self::REMOVED_FROM_FRIENDS_LIST => 'removed from %s friends list',
            self::REMOVED_FROM_CONVERSATION => '%s removed from conversation',
            self::LEFT_THE_CONVERSATION     => '%s left the conversation',
            self::REMOVED_CONVERSATION      => '%s removed conversation',
            self::ADDED_TO_CONVERSATION     => '%s added to conversation',
            self::CONVERSATION_NAME_CHANGED => 'conversation name changed by %s',
            default                         => 'conversation group created'
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
            self::FRIEND_REQUEST_RECEIVED   => 1,
            self::FRIEND_REQUEST_DENIED     => 2,
            self::FRIEND_REQUEST_ACCEPTED   => 3,
            self::REMOVED_FROM_FRIENDS_LIST => 4,
            self::REMOVED_FROM_CONVERSATION => 5,
            self::LEFT_THE_CONVERSATION     => 6,
            self::REMOVED_CONVERSATION      => 7,
            self::ADDED_TO_CONVERSATION     => 8,
            self::CONVERSATION_NAME_CHANGED => 9,
            default                         => 0
        };
    }
}
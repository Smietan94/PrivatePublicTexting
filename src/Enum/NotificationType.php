<?php

declare(strict_types=1);

namespace App\Enum; 

enum NotificationType: int
{
    case CONVERSATION_GROUP_CREATED = 0; // ✓
    case REMOVED_FROM_CONVERSATION  = 1; // ✓
    case LEFT_THE_CONVERSATION      = 2; // ✓
    case REMOVED_CONVERSATION       = 3; // ✓
    case ADDED_TO_CONVERSATION      = 4; // ✓
    case CONVERSATION_NAME_CHANGED  = 5; // ✓
    case FRIEND_REQUEST_RECEIVED    = 6; // ✓
    case FRIEND_REQUEST_DENIED      = 7; // ✓
    case FRIEND_REQUEST_ACCEPTED    = 8; // ✓
    case REMOVED_FROM_FRIENDS_LIST  = 9;

    /**
     * 
     * toString
     *
     * @return string
     */
    public function toString()
    {
        return match($this) {
            self::REMOVED_FROM_CONVERSATION => '%s removed %s from %s conversation',
            self::LEFT_THE_CONVERSATION     => '%s left the %s conversation',
            self::REMOVED_CONVERSATION      => '%s removed %s conversation',
            self::ADDED_TO_CONVERSATION     => '%s added %s to %s conversation',
            self::CONVERSATION_NAME_CHANGED => '%s change %s conversation name to %s',
            self::FRIEND_REQUEST_RECEIVED   => '%s is sending You friends request',
            self::FRIEND_REQUEST_DENIED     => '%s denied Your friends request',
            self::FRIEND_REQUEST_ACCEPTED   => '%s accepted Your friends request',
            self::REMOVED_FROM_FRIENDS_LIST => '%s removed You from friends list',
            default                         => '%s created %s conversation'
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
            self::REMOVED_FROM_CONVERSATION => 1,
            self::LEFT_THE_CONVERSATION     => 2,
            self::REMOVED_CONVERSATION      => 3,
            self::ADDED_TO_CONVERSATION     => 4,
            self::CONVERSATION_NAME_CHANGED => 5,
            self::FRIEND_REQUEST_RECEIVED   => 6,
            self::FRIEND_REQUEST_DENIED     => 7,
            self::FRIEND_REQUEST_ACCEPTED   => 8,
            self::REMOVED_FROM_FRIENDS_LIST => 9,
            default                         => 0
        };
    }
}
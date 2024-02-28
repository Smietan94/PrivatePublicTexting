<?php

declare(strict_types=1);

namespace App\Enum;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;

enum NotificationType: int
{
    case CONVERSATION_GROUP_CREATED = 0;
    case REMOVED_FROM_CONVERSATION  = 1;
    case LEFT_THE_CONVERSATION      = 2;
    case REMOVED_CONVERSATION       = 3;
    case ADDED_TO_CONVERSATION      = 4;
    case CONVERSATION_NAME_CHANGED  = 5;
    case FRIEND_REQUEST_RECEIVED    = 6;
    case FRIEND_REQUEST_DENIED      = 7;
    case FRIEND_REQUEST_ACCEPTED    = 8;
    case REMOVED_FROM_FRIENDS_LIST  = 9;

    /**
     * 
     * getMessage
     *
     * @return string
     */
    public function getMessage()
    {
        return match($this) {
            self::REMOVED_FROM_CONVERSATION => '%s removed %s from %s conversation',
            self::LEFT_THE_CONVERSATION     => '%s left the %s conversation',
            self::REMOVED_CONVERSATION      => '%s removed %s conversation',
            self::ADDED_TO_CONVERSATION     => '%s added %s to %s conversation',
            self::CONVERSATION_NAME_CHANGED => '%s changed %s conversation name to %s',
            self::FRIEND_REQUEST_RECEIVED   => '%s is sending You friends request',
            self::FRIEND_REQUEST_DENIED     => '%s denied Your friends request',
            self::FRIEND_REQUEST_ACCEPTED   => 'You and %s are friends now',
            self::REMOVED_FROM_FRIENDS_LIST => '%s removed You from friends list',
            default                         => '%s created %s conversation'
        };
    }

    /**
     * getRouteName
     *
     * @return string
     */
    public function getRouteName()
    {
        return match($this) {
            self::REMOVED_FROM_CONVERSATION => RoutePath::EMPTY_PATH,
            self::LEFT_THE_CONVERSATION     => RouteName::APP_CHAT_GROUP,
            self::REMOVED_CONVERSATION      => RoutePath::EMPTY_PATH,
            self::ADDED_TO_CONVERSATION     => RouteName::APP_CHAT_GROUP,
            self::CONVERSATION_NAME_CHANGED => RouteName::APP_CHAT_GROUP,
            self::FRIEND_REQUEST_RECEIVED   => RouteName::APP_FRIENDS_REQUESTS,
            self::FRIEND_REQUEST_DENIED     => RoutePath::EMPTY_PATH,
            self::FRIEND_REQUEST_ACCEPTED   => RouteName::APP_CHAT,
            self::REMOVED_FROM_FRIENDS_LIST => RoutePath::EMPTY_PATH,
            default                         => RouteName::APP_CHAT_GROUP
        };
    }

    /**
     * toString
     *
     * @return string
     */
    public function toString()
    {
        return match($this) {
            self::REMOVED_FROM_CONVERSATION  => 'removes from cronversations',
            self::LEFT_THE_CONVERSATION      => 'conversation lefts',
            self::REMOVED_CONVERSATION       => 'conversation removes',
            self::ADDED_TO_CONVERSATION      => 'adds to conversation',
            self::CONVERSATION_NAME_CHANGED  => 'conversation name changes',
            self::FRIEND_REQUEST_RECEIVED    => 'friend request receives',
            self::FRIEND_REQUEST_DENIED      => 'friend request rejections',
            self::FRIEND_REQUEST_ACCEPTED    => 'friend request accepts',
            self::REMOVED_FROM_FRIENDS_LIST  => 'removes from friends list',
            default                          => 'conversation group creation',
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
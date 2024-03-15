<?php

declare(strict_types=1);

namespace App\Entity\Constants;

class Constant
{
    // DEFATULT CHAT FILE STORAGE
    public const CHAT_FILES_STORAGE_PATH = '/conversation_attachments/conversation%d/';
    public const FILE_STORAGE_PATH       = '../var/storage%s';

    // FILE
    public const MAX_FILE_UPLOADS = 12;
    public const MAX_UPLOAD_SIZE  = 20;

    // MERCURE TOPICS
    public const NOTIFICATIONS      = 'notifications-%d';
    public const CONVERSATION_PRIV  = 'conversation.priv-%d';
    public const CONVERSATION_GROUP = 'conversation.group-%d';

    // PAGER
    public const MAX_NOTIFICATIONS_PER_PAGE = 10;
    public const MAX_MESSAGES_PER_PAGE      = 10;
    public const MAX_FRIENDS_PER_PAGE       = 6;
    public const MAX_IMGS_CAROUSEL_PAGE     = 9;

    // SESSION VALUE NAMES
    public const NOTIFICATIONS_ORDER_BY_DATE    = 'NOTIFICATIONS_ORDER_BY_DATE';
    public const NOTIFICATIONS_TYPES_TO_DISPLAY = 'NOTIFICATIONS_TYPES_TO_DISPLAY';

    // RESIZED IMG CONSTS
    public const MAX_RESIZED_WIDTH  = 200;
    public const MAX_RESIZED_HEIGHT = 150;

    // USER
    public const DELETED_USER_NAME_FORMAT  = 'chat_user_%s';
    public const DELETED_USER_EMAIL_FORMAT = 'chat_user_email_(%s)_%s';
}
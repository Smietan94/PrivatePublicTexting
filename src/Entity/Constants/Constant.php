<?php

declare(strict_types=1);

namespace App\Entity\Constants;

class Constant
{
    // DEFATULT CHAT FILE STORAGE
    public const CHAT_FILES_STORAGE_PATH = '/conversation_attachments/conversation%d/';

    // MERCURE TOPICS
    public const NOTIFICATIONS      = 'notifications-%d';
    public const CONVERSATION_PRIV  = 'conversation.priv-%d';
    public const CONVERSATION_GROUP = 'conversation.group-%d';
}
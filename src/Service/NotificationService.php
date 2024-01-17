<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Twig\Runtime\ConversationMemberRuntime;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private ConversationMemberRuntime $conversationProcesor,
        private HubInterface              $hub,
    ) {
    }

    /**
     * messagePreviewMercureUpdater
     *
     * @param  Conversation $conversation
     * @param  string       $message
     * @param  int          $senderId
     * @return void
     */
    public function messagePreviewMercureUpdater(Conversation $conversation, string $message, int $senderId): void
    {
        $topics = $this->conversationProcesor->getConversationTopics($conversation);

        $data = [
            'message'        => substr($message, 0, 20),
            'senderId'       => $senderId,
            'conversationId' => $conversation->getId(),
        ];

        $update = new Update(
            $topics[0],
            json_encode([
                'messagePreview' => $data
            ]),
            true
        );

        $this->hub->publish($update);
    }
}
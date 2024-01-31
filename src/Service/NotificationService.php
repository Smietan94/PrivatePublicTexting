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
        private ConversationMemberRuntime $conversationProcessor,
        private HubInterface              $hub
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
        $topics = $this->conversationProcessor->getConversationTopics($conversation);

        $data = [
            'message'        => substr($message, 0, 20),
            'senderId'       => $senderId,
            'conversationId' => $conversation->getId(),
        ];

        $update = new Update(
            $topics,
            json_encode([
                'messagePreview' => $data
            ]),
            true
        );

        $this->hub->publish($update);
    }

    /**
     * processFirstGroupMessagePreview
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processFirstGroupMessagePreview(Conversation $conversation): void
    {
        $topics  = $this->conversationProcessor->getConversationTopics($conversation);

        $update = new Update(
            $topics,
            json_encode([
                'conversationId' => $conversation->getId(),
            ]),
            true
        );

        $this->hub->publish($update);
    }

    /**
     * processConversationMemberRemove
     *
     * @param  Conversation $conversation
     * @param  int          $removedUserId
     * @return void
     */
    public function processConversationMemberRemove(Conversation $conversation, int $removedUserId): void
    {
        $topics = $this->conversationProcessor->getConversationTopics($conversation);
        $data   = [
            'conversationId' => $conversation->getId(),
            'removedUserId'  => $removedUserId,
        ];

        $update = new Update(
            $topics,
            json_encode([
                'removedUserData' => $data
            ]),
            true
        );

        $this->hub->publish($update);
    }

    /**
     * processNameChange
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processNameChange(Conversation $conversation): void
    {
        $topics = $this->conversationProcessor->getConversationTopics($conversation);
        $data   = [
            'conversationName' => $conversation->getName(),
            'conversationId'   => $conversation->getId()
        ];

        $update = new Update(
            $topics,
            json_encode([
                'conversationNameChangeData' => $data
            ]),
            true
        );

        $this->hub->publish($update);
    }

    public function processNewConversationMemberAddition(Conversation $conversation): void
    {
        $topics = $this->conversationProcessor->getConversationTopics($conversation);
        $data   = [
            'conversationId'       => $conversation->getId(),
            'isConversationUpdate' => true
        ];

        $update = new Update(
            $topics,
            json_encode([
                'newConversationData' => $data
            ])
        );

        $this->hub->publish($update);
    }
}
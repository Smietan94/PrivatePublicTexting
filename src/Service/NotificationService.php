<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use App\Twig\Runtime\ConversationMemberRuntime;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private ConversationMemberRuntime $conversationProcessor,
        private HubInterface              $hub,
        private NotificationRepository    $notificationRepository
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

        if (count($topics) > 0) {
            $data = ['messagePreview' => [
                'message'        => substr($message, 0, 20),
                'senderId'       => $senderId,
                'conversationId' => $conversation->getId(),
            ]];

            $this->publicMercureUpdate($topics, $data);
        }
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

        if (count($topics) > 0) {
            $data = ['conversationId' => $conversation->getId()];

            $this->publicMercureUpdate($topics, $data);
        }
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

        if (count($topics) > 0) {
            $data = ['removedUserData' => [
                'conversationId' => $conversation->getId(),
                'removedUserId'  => $removedUserId,
            ]];

            $this->publicMercureUpdate($topics, $data);
        }
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

        if (count($topics) > 0) {
            $data   = ['conversationNameChangeData' => [
                'conversationName' => $conversation->getName(),
                'conversationId'   => $conversation->getId()
            ]];
            $this->publicMercureUpdate($topics, $data);
        }
    }

    /**
     * processNewConversationMemberAddition
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processNewConversationMemberAddition(Conversation $conversation): void
    {
        $topics = $this->conversationProcessor->getConversationTopics($conversation);

        if (count($topics) > 0) {
            $data = ['newConversationData' =>[
                'conversationId'       => $conversation->getId(),
                'isConversationUpdate' => true,
            ]];
            $this->publicMercureUpdate($topics, $data);
        }
    }

    /**
     * processConversationRemove
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processConversationRemove(Conversation $conversation): void
    {
        $topics = $this->conversationProcessor->getConversationTopics($conversation);

        if (count($topics) > 0) {
            $data = ['removedConversationId' => $conversation->getId()];
            $this->publicMercureUpdate($topics, $data);
        }
    }

    /**
     * processConversationMemberRemoveNotification
     *
     * @param  User         $currentUser
     * @param  User         $removedUser
     * @param  Conversation $conversation
     * @return void
     */
    public function processConversationMemberRemoveNotification(User $currentUser, User $removedUser, Conversation $conversation): void
    {
        $notifications    = [];
        $format           = NotificationType::REMOVED_FROM_CONVERSATION->toString();
        $currentUserName  = $currentUser->getUsername();
        $removedUserName  = $removedUser->getUsername();
        $conversationName = $conversation->getName();

        foreach ($conversation->getConversationMembers() as $receiver) {
            $message = match (true) {
                $receiver === $removedUser => sprintf($format, $currentUserName, 'You', $conversationName),
                $receiver === $currentUser => sprintf($format, 'You', $removedUserName, $conversationName),
                default                    => sprintf($format, $currentUserName, $removedUserName, $conversationName)
            };

            array_push(
                $notifications,
                $this->notificationRepository->storeNotification(
                    NotificationType::REMOVED_FROM_CONVERSATION->toInt(),
                    $currentUser,
                    $receiver,
                    $message
                )
            );
        }
    }

    /**
     * processNameChangeNotification
     *
     * @param  User          $currentUser
     * @param  Conversation $conversation
     * @param  string        $oldConversationName
     * @return void
     */
    public function processNameChangeNotification(User $currentUser, Conversation $conversation, string $oldConversationName): void
    {
        $notifications       = [];
        $format              = NotificationType::CONVERSATION_NAME_CHANGED->toString();
        $newConversationName = $conversation->getName();
        $currentUserName     = $currentUser->getUsername();

        foreach ($conversation->getConversationMembers() as $receiver) {
            $message = match (true) {
                $receiver === $currentUser => sprintf($format, 'You', $oldConversationName, $newConversationName),
                default                    => sprintf($format, $currentUserName, $oldConversationName, $newConversationName)
            };

            array_push(
                $notifications,
                $this->notificationRepository->storeNotification(
                    NotificationType::CONVERSATION_NAME_CHANGED->toInt(),
                    $currentUser,
                    $receiver,
                    $message
                )
            );
        }
    }

    /**
     * processNewConversationMemberAdditionNotification
     *
     * @param  User         $currentUser
     * @param  User[]       $newMembers
     * @param  Conversation $conversation
     * @return void
     */
    public function processNewConversationMemberAdditionNotification(User $currentUser, array $newMembers, Conversation $conversation): void
    {
        $notifications       = [];
        $format              = NotificationType::ADDED_TO_CONVERSATION->toString();
        $currentUserName     = $currentUser->getUsername();
        $conversationName    = $conversation->getName();
        $newMembersUsernames = array_map(fn ($user) => $user->getUsername(), $newMembers);
        $processedUsernames  = $this->processNewMembersNames($newMembersUsernames);

        foreach ($conversation->getConversationMembers() as $receiver) {
            if (!in_array($receiver, $newMembers)) {
                $message = match (true) {
                    $receiver === $currentUser => sprintf($format, 'You', $processedUsernames, $conversationName),
                    default                    => sprintf($format, $currentUserName, $processedUsernames, $conversationName)
                };
            } else {
                $usernames = $this->processNewMembersUsernamesWhenIsReceiver($newMembersUsernames, $receiver->getUsername());
                $message   = sprintf($format, $currentUserName, $usernames, $conversationName);
            }

            array_push(
                $notifications,
                $this->notificationRepository->storeNotification(
                    NotificationType::ADDED_TO_CONVERSATION->toInt(),
                    $currentUser,
                    $receiver,
                    $message
                )
            );
        }
    }

    /**
     * processNewConversationGroupNotification
     *
     * @param  User         $currentUser
     * @param  Conversation $conversation
     * @return void
     */
    public function processNewConversationGroupNotification(User $currentUser, Conversation $conversation): void
    {
        $notifications = $this->processConversationNotification(
            NotificationType::CONVERSATION_GROUP_CREATED,
            $currentUser,
            $conversation
        );
    }

    /**
     * processConversationLeftNotification
     *
     * @param  User         $currentUser
     * @param  Conversation $conversation
     * @return void
     */
    public function processConversationLeftNotification(User $currentUser, Conversation $conversation): void
    {
        $notifications = $this->processConversationNotification(
            NotificationType::LEFT_THE_CONVERSATION,
            $currentUser,
            $conversation
        );
    }

    /**
     * processConversationRemoveNotification
     *
     * @param  User         $currentUser
     * @param  Conversation $conversation
     * @return void
     */
    public function processConversationRemoveNotification(User $currentUser, Conversation $conversation): void 
    {
        $notifications = $this->processConversationNotification(
            NotificationType::REMOVED_CONVERSATION,
            $currentUser,
            $conversation
        );
    }

    public function processFriendStatusNotification(NotificationType $type, User $sender, User $receiver): Notification
    {
        $format  = $type->toString();
        $message = sprintf($format, $sender->getUsername());

        return $this->notificationRepository->storeNotification(
            $type->toInt(),
            $sender,
            $receiver,
            $message
        );
    }

    /**
     * processConversationNotification
     *
     * @param  NotificationType $type
     * @param  User             $currentUser
     * @param  Conversation     $conversation
     * @return Notification[]
     */
    private function processConversationNotification(NotificationType $type, User $currentUser, Conversation $conversation): array
    {
        $notifications    = [];
        $format           = $type->toString();
        $currentUserName  = $currentUser->getUsername();
        $conversationName = $conversation->getName();

        foreach ($conversation->getConversationMembers() as $receiver) {
            $message = match (true) {
                $receiver === $currentUser => sprintf($format, 'You\'ve', $conversationName),
                default                    => sprintf($format, $currentUserName, $conversationName)
            };

            array_push(
                $notifications,
                $this->notificationRepository->storeNotification(
                    $type->toInt(),
                    $currentUser,
                    $receiver,
                    $message
                )
            );
        };

        return $notifications;
    }

    /**
     * publicMercureUpdate
     *
     * @param  string[] $topics
     * @param  array    $data
     * @return void
     */
    private function publicMercureUpdate(array $topics, array $data): void
    {
        $update = new Update(
            $topics,
            json_encode($data),
            true
        );

        $this->hub->publish($update);
    }

    /**
     * processNewMembersUsernamesWhenIsReceiver
     *
     * @param  string[] $newMembersUsernames
     * @param  string   $targetUsername
     * @return string
     */
    private function processNewMembersUsernamesWhenIsReceiver(array $newMembersUsernames, string $targetUsername): string
    {
        if (count($newMembersUsernames) > 1) {
            $index                       = array_search($targetUsername, $newMembersUsernames);
            $newMembersUsernames[$index] = 'You';
        }

        return $this->processNewMembersNames($newMembersUsernames);
    }

    /**
     * processNewMembersNames
     *
     * @param  string[] $newMembersUsernames
     * @return string
     */
    private function processNewMembersNames(array $newMembersUsernames): string
    {
        if (count($newMembersUsernames) > 1) {
            $lastName = array_slice($newMembersUsernames, -1)[0];
            $names    = implode(', ', array_slice($newMembersUsernames, 0, -1));

            return sprintf('%s and %s', $names, $lastName);
        }

        return $newMembersUsernames[0];
    }
}
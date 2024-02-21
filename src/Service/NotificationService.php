<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\Conversation;
use App\Entity\FriendRequest;
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
    public function messagePreviewMercureUpdater(Conversation $conversation): void
    {
        $data = ['messagePreview' => [
            'conversationId' => $conversation->getId(),
        ]];

        $this->processConversationMercureUpdate($conversation, $data);
    }

    /**
     * processFirstGroupMessagePreview
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processFirstGroupMessagePreview(Conversation $conversation): void
    {
        $data = ['conversationId' => $conversation->getId()];

        $this->processConversationMercureUpdate($conversation, $data);
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
        $data = ['removedUserData' => [
            'conversationId' => $conversation->getId(),
            'removedUserId'  => $removedUserId,
        ]];

        $this->processConversationMercureUpdate($conversation, $data);
    }

    /**
     * processNameChange
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processNameChange(Conversation $conversation): void
    {
        $data   = ['conversationNameChangeData' => [
            'conversationName' => $conversation->getName(),
            'conversationId'   => $conversation->getId()
        ]];

        $this->processConversationMercureUpdate($conversation, $data);
    }

    /**
     * processNewConversationMemberAddition
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processNewConversationMemberAddition(Conversation $conversation): void
    {
        $data = ['newConversationData' =>[
            'conversationId'       => $conversation->getId(),
            'isConversationUpdate' => true,
        ]];

        $this->processConversationMercureUpdate($conversation, $data);
    }

    /**
     * processConversationRemove
     *
     * @param  Conversation $conversation
     * @return void
     */
    public function processConversationRemove(Conversation $conversation): void
    {
        $data = ['removedConversationId' => $conversation->getId()];

        $this->processConversationMercureUpdate($conversation, $data);
    }

    /**
     * processFriendRemove
     *
     * @param  User $currentUser
     * @param  User $friendToRm
     * @return void
     */
    public function processFriendRemove(User $currentUser, User $friendToRm): void
    {
        $topic = sprintf(Constant::NOTIFICATIONS, $friendToRm->getId());
        $data  = ['friendRemoveData' => [
            'removingUserId' => $currentUser->getId(),
            'removedUserId'  => $friendToRm->getId()
        ]];

        $this->publishMercureUpdate($topic, $data);
    }

    /**
     * processFriendRequestReceive
     *
     * @param  FriendRequest $friendRequest
     * @return void
     */
    public function processFriendRequestReceive(FriendRequest $friendRequest): void
    {
        $topic = sprintf(Constant::NOTIFICATIONS, $friendRequest->getRequestedUser()->getId());
        $data  = ['receivedFriendRequestId' => $friendRequest->getId()];

        $this->publishMercureUpdate($topic, $data);
    }

    /**
     * processFriendReqeustDenied
     *
     * @param  FriendRequest $friendRequest
     * @return void
     */
    public function processFriendRequestDenied(FriendRequest $friendRequest): void
    {
        $topic = sprintf(Constant::NOTIFICATIONS, $friendRequest->getRequestingUser()->getId());
        $data  = ['deniedFriendRequestId' => $friendRequest->getId()];

        $this->publishMercureUpdate($topic, $data);
    }

    /**
     * processFriendRequestAccept
     *
     * @param  FriendRequest $friendRequest
     * @return void
     */
    public function processFriendRequestAccept(FriendRequest $friendRequest): void
    {
        $topic = sprintf(Constant::NOTIFICATIONS, $friendRequest->getRequestingUser()->getId());
        $data  = ['acceptedFriendRequestId' => $friendRequest->getId()];

        $this->publishMercureUpdate($topic, $data);
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
        $format           = NotificationType::REMOVED_FROM_CONVERSATION->getMessage();
        $currentUserName  = $currentUser->getUsername();
        $removedUserName  = $removedUser->getUsername();
        $conversationName = $conversation->getName();
        $conversationId   = $conversation->getId();

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
                    $message,
                    ($receiver !== $removedUser) ? $conversationId: null
                )
            );
        }
    }

    /**
     * processNameChangeNotification
     *
     * @param  User         $currentUser
     * @param  Conversation $conversation
     * @param  string       $oldConversationName
     * @return void
     */
    public function processNameChangeNotification(User $currentUser, Conversation $conversation, string $oldConversationName): void
    {
        $notifications       = [];
        $format              = NotificationType::CONVERSATION_NAME_CHANGED->getMessage();
        $newConversationName = $conversation->getName();
        $currentUserName     = $currentUser->getUsername();
        $conversationId      = $conversation->getId();

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
                    $message,
                    $conversationId
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
        $format              = NotificationType::ADDED_TO_CONVERSATION->getMessage();
        $currentUserName     = $currentUser->getUsername();
        $conversationName    = $conversation->getName();
        $conversationId      = $conversation->getId();
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
                    $message,
                    $conversationId
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

    /**
     * processFriendStatusNotification
     *
     * @param  NotificationType $type
     * @param  User             $sender
     * @param  User             $receiver
     * @param  ?int             $conversationId
     * @return Notification|Notification[]
     */
    public function processFriendStatusNotification(NotificationType $type, User $sender, User $receiver, ?int $conversationId = null): Notification|array
    {
        $format  = $type->getMessage();

        if ($type === NotificationType::FRIEND_REQUEST_ACCEPTED) {
            return $this->processFriendAcceptNotification($type, [$sender, $receiver], $conversationId);
        }

        $message = sprintf($format, $sender->getUsername());

        return $this->notificationRepository->storeNotification(
            $type->toInt(),
            $sender,
            $receiver,
            $message,
            $conversationId
        );
    }

    /**
     * processFriendAcceptNotification
     *
     * @param  NotificationType $type
     * @param  User[]           $friends
     * @return Notification[]
     */
    public function processFriendAcceptNotification(NotificationType $type, array $friends, int $conversationId): array
    {
        $notifications = [];

        foreach ($friends as $friend) {
            $sender  = ($friends[0] === $friend) ? $friends[1]: $friends[0];
            $message = sprintf($type->getMessage(), $sender->getUsername());

            array_push(
                $notifications,
                $this->notificationRepository->storeNotification(
                    $type->toInt(),
                    $sender,
                    $friend,
                    $message,
                    $conversationId
                )
            );
        };

        return $notifications;
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
        $format           = $type->getMessage();
        $currentUserName  = $currentUser->getUsername();
        $conversationName = $conversation->getName();

        foreach ($conversation->getConversationMembers() as $receiver) {
            $message = match (true) {
                $receiver === $currentUser => sprintf($format, 'You\'ve', $conversationName),
                default                    => sprintf($format, $currentUserName, $conversationName)
            };

            $conversationId = match (true) {
                $type === NotificationType::CONVERSATION_GROUP_CREATED => $conversation->getId(),
                $type === NotificationType::LEFT_THE_CONVERSATION      => ($receiver === $currentUser) ? $conversation->getId(): null,
                default                                                => null
            };

            array_push(
                $notifications,
                $this->notificationRepository->storeNotification(
                    $type->toInt(),
                    $currentUser,
                    $receiver,
                    $message,
                    $conversationId
                )
            );
        };

        return $notifications;
    }

    /**
     * publishMercureUpdate
     *
     * @param  string|string[] $topics
     * @param  array    $data
     * @return void
     */
    private function publishMercureUpdate(string|array $topics, array $data): void
    {
        $update = new Update(
            $topics,
            json_encode($data),
            true
        );

        $this->hub->publish($update);
    }

    /**
     * processConversationMercureUpdate
     *
     * @param  Conversation $conversation
     * @param  array        $data
     * @return void
     */
    private function processConversationMercureUpdate(Conversation $conversation, array $data): void
    {
        $topics = $this->conversationProcessor->getConversationTopics($conversation);

        if (count($topics) > 0) {
            $this->publishMercureUpdate($topics, $data);
        }
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
        if (count($newMembersUsernames) > 0) {
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
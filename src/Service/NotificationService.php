<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\Conversation;
use App\Entity\FriendRequest;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Exception\InvalidNotificationTypeException;
use App\Form\NotificationsFilterType;
use App\Repository\NotificationRepository;
use App\Twig\Runtime\ConversationMemberRuntime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Order;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private ConversationMemberRuntime $conversationProcessor,
        private HubInterface              $hub,
        private NotificationRepository    $notificationRepository,
        private FormFactoryInterface      $formFactory,
    ) {
    }

    /**
     * gets notifications pager
     *
     * @param  int    $page
     * @param  User   $currentUser
     * @param  string $order
     * @param  array  $notificationsTpes
     * @return ?Pagerfanta
     */
    public function getNotificationsPager(int $page, User $currentUser, string $order, array $notificationsTypes): ?Pagerfanta
    {
        $sortedNotificationsList = $this->sortReceivedNotificationsCollection(
            $currentUser->getReceivedNotifications(), [
            'order'             => $order,
            'notificationTypes' => $notificationsTypes
        ]);


        if (count($sortedNotificationsList) === 0) {
            return null;
        }

        $adapter = new ArrayAdapter($sortedNotificationsList);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            $page,
            Constant::MAX_NOTIFICATIONS_PER_PAGE
        );
    }

    /**
     * sorts notifications
     *
     * @param  Collection<int, Notification> $receivedNotifaction
     * @param  array                         $params
     * @return Notification[]
     */
    public function sortReceivedNotificationsCollection(Collection $receivedNotifaction, array $params): array
    {
        $arrToSort  = new ArrayCollection($receivedNotifaction->toArray());
        $criteria   = new Criteria();
        $order      = $params['order'];
        $notifTypes = $params['notificationTypes'];

        if (count($notifTypes) > 0) {
            $expr     = new Comparison('notificationType', Comparison::IN, $notifTypes);
            $criteria = $criteria->andWhere($expr);
        }

        $criteria = $criteria->orderBy([
            'displayed' => Order::Ascending,
            'createdAt' => $order,
        ]);

        return $arrToSort->matching($criteria)->toArray();
    }

    /**
     * message preview mercure updater
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
     * process first group message preview
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
     * process conversation member remove
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
     * process conversation name change
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
     * process new conversation member addition
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
     * process conversation remove
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
     * process friend remove
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
     * process friend request receive
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
     * process friend request deny
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
     * process friend request accept
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
     * process conversatino remove notitication
     *
     * @param  User          $currentUser
     * @param  User          $removedUser
     * @param  Conversation  $conversation
     * @return Notification[]
     */
    public function processConversationMemberRemoveNotification(User $currentUser, User $removedUser, Conversation $conversation): array
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

        return $notifications;
    }

    /**
     * process conversation name change notification
     *
     * @param  User          $currentUser
     * @param  Conversation  $conversation
     * @param  string        $oldConversationName
     * @return Notification[]
     */
    public function processNameChangeNotification(User $currentUser, Conversation $conversation, string $oldConversationName): array
    {
        $notifications       = [];
        $format              = NotificationType::CONVERSATION_NAME_CHANGED->getMessage();
        $currentUserName     = $currentUser->getUsername();
        $newConversationName = $conversation->getName();
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

        return $notifications;
    }

    /**
     * process new conversation member addition notification
     *
     * @param  User          $currentUser
     * @param  User[]        $newMembers
     * @param  Conversation  $conversation
     * @return Notification[]
     */
    public function processNewConversationMemberAdditionNotification(User $currentUser, array $newMembers, Conversation $conversation): array
    {
        $notifications       = [];
        $currentUserName     = $currentUser->getUsername();
        $conversationName    = $conversation->getName();
        $conversationId      = $conversation->getId();
        $newMembersUsernames = array_map(fn ($user) => $user->getUsername(), $newMembers);
        $processedUsernames  = $this->processNewMembersNames($newMembersUsernames);
        $conversationMembers = $conversation->getConversationMembers();

        foreach ($conversationMembers as $receiver) {
            $message = $this->processMessageForMemberAdditionNotification(
                $receiver,
                $currentUser,
                $newMembersUsernames,
                $currentUserName,
                $conversationName,
                $processedUsernames
            );

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

        return $notifications;
    }

    /**
     * process new conversation group notification
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
     * process conversation left notification
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
     * process conversation remove notification
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
     * process friend status notification
     *
     * @param  NotificationType $type
     * @param  User             $sender
     * @param  User             $receiver
     * @param  ?int             $conversationId
     * @return Notification|Notification[]
     */
    public function processFriendStatusNotification(NotificationType $type, User $sender, User $receiver, ?int $conversationId = null): Notification|array
    {
        $format = $type->getMessage();

        if (!in_array($type, [NotificationType::FRIEND_REQUEST_ACCEPTED, NotificationType::FRIEND_REQUEST_DENIED, NotificationType::FRIEND_REQUEST_RECEIVED])) {
            throw new InvalidNotificationTypeException(sprintf('%s is invalid notification type.', $type->toString()), 400);
        }

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
     * process friend accept notification
     *
     * @param  NotificationType $type
     * @param  User[]           $friends
     * @return Notification[]
     */
    private function processFriendAcceptNotification(NotificationType $type, array $friends, int $conversationId): array
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
     * create notifications filter form
     *
     * @return FormInterface
     */
    public function createNotificationsFilterForm(): FormInterface
    {
        return $this->formFactory->create(NotificationsFilterType::class);
    }

    /**
     * process conversation notification
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
     * publish mercure update
     *
     * @param  string|string[] $topics
     * @param  array           $data
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
     * process conversation mercure update
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
     * process new members usernames when current user is receiver
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
     * process new members names
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

    /**
     * process message for member addition notification
     * 
     * @param User     $receiver
     * @param User     $currentUser
     * @param User[]   $newMembers
     * @param string[] $newMembersUsernames
     * @param string   $currentUserName
     * @param string   $conversationName
     * @param string   $processedUsernames
     *
     * @return string
     */
    private function processMessageForMemberAdditionNotification(
        User   $receiver,
        User   $currentUser,
        array  $newMembersUsernames,
        string $currentUserName,
        string $conversationName,
        string $processedUsernames,
    ): string {
        $format           = NotificationType::ADDED_TO_CONVERSATION->getMessage();
        $receiverUsername = $receiver->getUsername();

        if (!in_array($receiverUsername, $newMembersUsernames)) {
            $message = match (true) {
                $receiver === $currentUser => sprintf($format, 'You', $processedUsernames, $conversationName),
                default                    => sprintf($format, $currentUserName, $processedUsernames, $conversationName)
            };
        } else {
            $usernames = $this->processNewMembersUsernamesWhenIsReceiver($newMembersUsernames, $receiverUsername);
            $message   = sprintf($format, $currentUserName, $usernames, $conversationName);
        }

        return $message;
    }
}
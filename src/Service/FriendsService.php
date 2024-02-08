<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\FriendStatus;
use App\Enum\NotificationType;
use App\Repository\FriendHistoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class FriendsService
{
    public function __construct(
        private UserRepository          $userRepository,
        private FriendHistoryRepository $friendHistoryRepository,
        private EntityManagerInterface  $entityManager,
        private NotificationService     $notificationService
    ) {
    }

    /**
     * getHowLongFriends
     *
     * @param  User $currentUser
     * @return array
     */
    public function getHowLongFriends(User $currentUser): array
    {
        // collectiong names and dates of received requests
        $receivedFriendsHistory = $currentUser->getReceivedFriendHistory()->toArray();

        $receivedFriendNames = $this->getReceivedFriendsNames($receivedFriendsHistory);
        $receivedFriendDates = $this->getDates($receivedFriendsHistory);

        // collectiong names and dates of sent requests
        $friendsSentHistory = $currentUser->getSentFriendHistory()->toArray();

        $sentFriendNames = $this->getSentFriendsNames($friendsSentHistory);
        $sentFriendDates = $this->getDates($friendsSentHistory);

        return array_combine(
            array_merge($receivedFriendNames, $sentFriendNames),
            array_merge($receivedFriendDates, $sentFriendDates)
        );
    }

    /**
     * removeFriend
     *
     * @param  User $currentUser
     * @param  User $friend
     * @return void
     */
    public function removeFriend(User $currentUser, User $friend): void
    {
        $friendHistory = $this->friendHistoryRepository->getFriendHistory($currentUser, $friend);

        $currentUser->removeFriend($friend);
        $friendHistory->setStatus(FriendStatus::DELETED->value);

        $this->notificationService->processFriendStatusNotification(
            NotificationType::REMOVED_FROM_FRIENDS_LIST,
            $currentUser,
            $friend
        );

        $this->entityManager->flush();
    }

    /**
     * getSentFriendsHistory
     *
     * @param  FriendHistory[] $friendsSentHistory
     * @return array
     */
    public function getSentFriendsNames(array $friendsSentHistory): array
    {
        return array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getRequestedUser()->getUsername() : null;
        }, $friendsSentHistory));
    }

    /**
     * getReceivedFriendsNames
     *
     * @param  FriendHistory[] $receivedFriendsHistory
     * @return array
     */
    public function getReceivedFriendsNames(array $receivedFriendsHistory): array
    {
        return array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getRequestingUser()->getUsername() : null;
        }, $receivedFriendsHistory));
    }

    /**
     * getDates
     *
     * @param  FriendHistory[] $friendHistory
     * @return \DateTime[] array
     */
    public function getDates(array $friendHistory): array
    {
        return array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getCreatedAt() : null;
        }, $friendHistory));
    }
}

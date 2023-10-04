<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\FriendStatus;
use App\Repository\FriendHistoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class FriendsService
{
    public function __construct(
        private UserRepository $userRepository,
        private FriendHistoryRepository $friendHistoryRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function getHowLongFriends(User $currentUser): array
    {
        // retriving names and dates of received requests
        $receivedFriendHistory = $currentUser->getReceivedFriendHistory()->toArray();
        // dd($currentUser->getFriends());

        $receivedFriendNames = array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getRequestingUser()->getUsername() : null;
        }, $receivedFriendHistory));
        $receivedFriendDates = array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getCreatedAt() : null;
        }, $receivedFriendHistory));

        // retriving names and dates of sent requests
        $friendSentHistory = $currentUser->getSentFriendHistory()->toArray();

        $sentFriendNames = array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getRequestedUser()->getUsername() : null;
        }, $friendSentHistory));
        $sentFriendDates = array_filter(array_map(function ($request) {
            return $request->getStatus() === FriendStatus::ACCEPTED->value ? $request->getCreatedAt() : null;
        }, $friendSentHistory));

        // dd(array_combine($receivedFriendNames + $sentFriendNames, $receivedFriendDates + $sentFriendDates));
        return array_combine(
            $receivedFriendNames + $sentFriendNames,
            $receivedFriendDates + $sentFriendDates
        );
    }

    public function removeFriend(string $username, int $friendId): void
    {
        $currentUser   = $this->userRepository->findOneBy(['username' => $username]);
        $friend        = $this->userRepository->find($friendId);
        $friendHistory = $this->friendHistoryRepository->getFriendHistory($currentUser, $friend);

        $currentUser->removeFriend($friend);
        $friend->removeFriend($currentUser);
        $friendHistory->setStatus(FriendStatus::DELETED->value);

        $this->entityManager->flush();
    }
}

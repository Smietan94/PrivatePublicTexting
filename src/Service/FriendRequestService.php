<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FriendRequest;
use App\Entity\FriendHistory;
use App\Entity\User;
use App\Repository\FriendHistoryRepository;
use App\Repository\FriendRequestRepository;
use Doctrine\Common\Cache\Psr6\InvalidArgument;
use Doctrine\ORM\EntityManagerInterface;

class FriendRequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FriendRequestRepository $friendRequestRepository,
        private FriendHistoryRepository $friendHistoryRepository,
    ) {
    }

    public function acceptRequest(User $currentUser, int $requestId, int $status): FriendHistory
    {
        $request = $this->friendRequestRepository->find($requestId);

        if (!($currentUser === $request->getRequestedUser())) {
            throw new InvalidArgument(message: "Invalid User");
        }

        $requestingUser = $request->getRequestingUser();
        $currentUser->addFriend($requestingUser);
        $requestingUser->addFriend($currentUser);

        return $this->deleteRequest($request, $status);
    }

    // Deleting request record from requests table, and adding it to FriendHistory
    public function deleteRequest(int|FriendRequest $request, int $status): FriendHistory
    {
        if (!($request instanceof FriendRequest)) {
            $request = $this->friendRequestRepository->find($request);
        }

        $newFriendHistory = new FriendHistory();
        $newFriendHistory->setStatus($status);
        $newFriendHistory->setSentAt($request->getCreatedAt());
        $newFriendHistory->setRequestedUser($request->getRequestedUser());
        $newFriendHistory->setRequestingUser($request->getRequestingUser());

        $this->entityManager->remove($request);
        $this->entityManager->persist($newFriendHistory);
        $this->entityManager->flush();

        return $newFriendHistory;
    }
}
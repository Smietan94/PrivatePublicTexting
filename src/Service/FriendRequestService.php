<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FriendRequest;
use App\Entity\FriendHistory;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Repository\ConversationRepository;
use App\Repository\FriendHistoryRepository;
use App\Repository\FriendRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class FriendRequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FriendRequestRepository $friendRequestRepository,
        private FriendHistoryRepository $friendHistoryRepository,
        private ConversationRepository $conversationRepository
    ) {
    }

    /**
     * acceptRequest
     *
     * @param  User $currentUser
     * @param  FriendRequest $request
     * @param  int $status
     * @return FriendHistory
     */
    public function acceptRequest(User $currentUser, FriendRequest $request, int $status): FriendHistory
    {
        // collecting requesting user
        $requestingUser = $request->getRequestingUser();

        $currentUser->addFriend($requestingUser);
        // $requestingUser->addFriend($currentUser);

        // checks if conversation already exists (users could be friends earlier) then creating conversation (or not if it already exists)
        if ($this->conversationRepository->getFriendConversation($currentUser, $requestingUser) == null) {
            $this->conversationRepository->storeConversation(
                $currentUser,
                [$requestingUser],
                ConversationType::SOLO->toInt()
            );
        }

        return $this->deleteRequestAndSetHistory($request, $status);
    }

    // Deleting request record from requests table, and adding it to FriendHistory
    /**
     * deleteRequestAndSetHistory
     *
     * @param  FriendRequest $request
     * @param  int $status
     * @return FriendHistory
     */
    public function deleteRequestAndSetHistory(FriendRequest $request, int $status): FriendHistory
    {
        // creating new instace of friend history
        $newFriendHistory = new FriendHistory();
        $newFriendHistory->setStatus($status);
        $newFriendHistory->setSentAt($request->getCreatedAt());
        $newFriendHistory->setRequestedUser($request->getRequestedUser());
        $newFriendHistory->setRequestingUser($request->getRequestingUser());

        // removing request and saving new record to friend history
        $this->entityManager->remove($request);
        $this->entityManager->persist($newFriendHistory);
        $this->entityManager->flush();

        return $newFriendHistory;
    }
}
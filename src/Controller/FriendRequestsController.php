<?php

namespace App\Controller;

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendStatus;
use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;
use App\Service\FriendRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FriendRequestsController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FriendRequestRepository $friendRequestRepository,
        private EntityManagerInterface $entityManager,
        private FriendRequestService $friendRequestService,
    ) {
        // collecting logged user
        $username = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route('/friendsRequests', name: 'app_friends_requests')]
    public function friendRequest(): Response
    {
        // rendering friends list
        return $this->render('friend_requests/index.html.twig', [
            'received' => $this->currentUser->getReceivedFriendRequests()->toArray(),
            'sent'     => $this->currentUser->getSentFriendRequests()->toArray(),
        ]);
    }

    #[Route('/sendFriendRequest', methods: ['POST'], name: 'app_send_friend_request')]
    public function sendFriendRequest(Request $request): Response
    {
        // collecting requested user
        $requestUserId = (int) $request->request->get('requestUserId');
        $requestedUser = $this->userRepository->find($requestUserId);

        // check if user already requested
        if ($this->friendRequestRepository->getFriendRequest(
            $this->currentUser,
            $requestedUser,
            FriendStatus::PENDING->value
        ))
        {
            $this->addFlash('error', 'You already sent this request');
            return $this->redirectToRoute('app_friends_requests');
        }

        $this->friendRequestRepository->setNewFriendRequest($this->currentUser, $requestedUser);

        $this->addFlash('success', 'Friend Request Sent');
        return $this->redirectToRoute('app_friends_requests');
    }

    #[Route('/friendRequests/accept', methods: ['POST'], name: 'app_accept_friend_request')]
    public function accept(Request $request): Response
    {
        // friend request validation
        $friendRequest = $this->preprocessFriendRequest($request, FriendStatus::ACCEPTED->toString());

        // if friend request does not extist routing back to friend requests list
        if (!$friendRequest) {
            $this->redirectToRoute('app_friends_requests');
        }

        $this->friendRequestService->acceptRequest(
            $this->currentUser,
            $friendRequest, 
            FriendStatus::ACCEPTED->value
        );

        $this->addFlash('success', 'You are friends now');

        return $this->redirectToRoute('app_friends_requests');
    }

    #[Route('/friendRequests/decline', methods: ['POST'], name: 'app_decline_friend_request')]
    public function decline(Request $request): Response
    {
        // friend request validation
        $friendRequest = $this->preprocessFriendRequest($request, FriendStatus::REJECTED->toString());

        // if friend request does not extist routing back to friend requests list
        if (!$friendRequest) {
            $this->redirectToRoute('app_friends_requests');
        }

        $this->friendRequestService->deleteRequestAndSetHistory($friendRequest, FriendStatus::REJECTED->value);

        return $this->redirectToRoute('app_friends_requests');
    }

    #[Route('/friendRequest/cancel', methods: ['POST'], name: 'app_cancel_friend_request')]
    public function cancel(Request $request): Response
    {
        // friend request validation
        $friendRequest = $this->preprocessFriendRequest($request, FriendStatus::CANCELLED->toString());

        // if friend request does not extist routing back to friend requests list
        if (!$friendRequest) {
            $this->redirectToRoute('app_friends_requests');
        }

        $this->friendRequestService->deleteRequestAndSetHistory($friendRequest, FriendStatus::CANCELLED->value);

        return $this->redirectToRoute('app_friends_requests');
    }

    private function preprocessFriendRequest(Request $request, string $status): ?FriendRequest
    {
        // collecting friend request
        $friendRequestId = (int) $request->request->get($status);
        $friendRequest   = $this->friendRequestRepository->find($friendRequestId);

        // if no friend request returning null and flash message
        if (!$friendRequest) {
            $this->addFlash('error', 'Friend request does not exists');
            return null;
        }

        // if current user not appears in friend request as requesting or requested it returns null and flash message
        if (($this->currentUser !== $friendRequest->getRequestedUser()) && ($this->currentUser !== $friendRequest->getRequestingUser())) {
            $this->addFlash('error', 'Invalid friend request');
            return null;
        }

        return $friendRequest;
    }
}

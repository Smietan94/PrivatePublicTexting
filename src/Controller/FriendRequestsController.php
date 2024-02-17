<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\FriendRequest;
use App\Entity\User;
use App\Enum\FriendStatus;
use App\Enum\NotificationType;
use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;
use App\Service\FriendRequestService;
use App\Service\NotificationService;
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
        private Security                $security,
        private UserRepository          $userRepository,
        private FriendRequestRepository $friendRequestRepository,
        private EntityManagerInterface  $entityManager,
        private FriendRequestService    $friendRequestService,
        private NotificationService     $notificationService
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * friendRequest
     *
     * @return Response
     */
    #[Route(
        RoutePath::FRIENDS_REQUEST,
        name: RouteName::APP_FRIENDS_REQUESTS
    )]
    public function friendRequest(): Response
    {
        // rendering friends list
        return $this->render('friend_requests/index.html.twig', [
            'received' => $this->currentUser->getReceivedFriendRequests()->toArray(),
            'sent'     => $this->currentUser->getSentFriendRequests()->toArray(),
        ]);
    }

    /**
     * renderReceivedRequests
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::RECEIVED_FRIENDS_REQUESTS,
        name: RouteName::APP_RECEIVED_FRIENDS_REQUESTS
    )]
    public function renderReceivedRequests(Request $request): Response
    {
        return $this->render('friend_requests/_receivedRequestsList.html.twig', [
            'received' => $this->currentUser->getReceivedFriendRequests()->toArray()
        ]);
    }
    
    /**
     * renderSentRequests
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SENT_FRIENDS_REQUESTS,
        name: RouteName::APP_SENT_FRIENDS_REQUESTS
    )]
    public function renderSentRequests(Request $request): Response
    {
        return $this->render('friend_requests/_sentRequestsList.html.twig', [
            'sent' => $this->currentUser->getSentFriendRequests()->toArray()
        ]);
    }

    /**
     * sendFriendRequest
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SEND_FRIENDS_REQUEST,
        methods: ['POST'],
        name: RouteName::APP_SEND_FRIEND_REQUEST
    )]
    public function sendFriendRequest(Request $request): Response
    {
        // collecting requested user
        $requestUserId = (int) $request->request->get('requestUserId');
        $requestedUser = $this->userRepository->find($requestUserId);

        // check if user already requested
        if ($this->friendRequestRepository->getFriendRequest($this->currentUser, $requestedUser, FriendStatus::PENDING->toInt())) {
            $this->addFlash('error', 'You already sent this request');
            return $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
        }

        $this->friendRequestRepository->setNewFriendRequest($this->currentUser, $requestedUser);

        $this->addFlash('success', 'Friend Request Sent');
        return $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
    }

    /**
     * accept
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::ACCEPT_FRIEND_REQUEST,
        methods: ['POST'],
        name: RouteName::APP_ACCEPT_FRIEND_REQUEST
    )]
    public function accept(Request $request): Response
    {
        // friend request validation
        $friendRequest = $this->preprocessFriendRequest($request, FriendStatus::ACCEPTED->toString());

        // if friend request does not extist routing back to friend requests list
        if (!$friendRequest) {
            $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
        }

        $this->friendRequestService->acceptRequest(
            $this->currentUser,
            $friendRequest, 
            FriendStatus::ACCEPTED->value
        );

        $this->addFlash('success', 'You are friends now');

        return $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
    }

    /**
     * deny
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::DENY_FRIEND_REQUEST,
        methods: ['POST'],
        name: RouteName::APP_DENY_FRIEND_REQUEST
    )]
    public function deny(Request $request): Response
    {
        // friend request validation
        $friendRequest = $this->preprocessFriendRequest($request, FriendStatus::REJECTED->toString());

        // if friend request does not extist routing back to friend requests list
        if (!$friendRequest) {
            $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
        }

        $this->notificationService->processFriendStatusNotification(
            NotificationType::FRIEND_REQUEST_DENIED,
            $friendRequest->getRequestingUser(),
            $friendRequest->getRequestedUser()
        );

        $this->notificationService->processFriendRequestDenied(
            $friendRequest,
            'deniedFriendRequestId'
        );

        $this->friendRequestService->deleteRequestAndSetHistory($friendRequest, FriendStatus::REJECTED->value);

        return $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
    }

    /**
     * cancel
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::CANCEL_FRIEND_REQUEST,
        methods: ['POST'],
        name: RouteName::APP_CANCEL_FRIEND_REQUEST
    )]
    public function cancel(Request $request): Response
    {
        // friend request validation
        $friendRequest = $this->preprocessFriendRequest($request, FriendStatus::CANCELLED->toString());

        // if friend request does not extist routing back to friend requests list
        if (!$friendRequest) {
            $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
        }

        $this->friendRequestService->deleteRequestAndSetHistory($friendRequest, FriendStatus::CANCELLED->toInt());

        return $this->redirectToRoute(RouteName::APP_FRIENDS_REQUESTS);
    }
    
    /**
     * preprocessFriendRequest
     *
     * @param  Request $request
     * @param  string  $status
     * @return FriendRequest
     */
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

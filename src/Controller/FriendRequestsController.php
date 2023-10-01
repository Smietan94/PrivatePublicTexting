<?php

namespace App\Controller;

use App\Repository\FriendRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Role\Role;

class FriendRequestsController extends AbstractController
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private FriendRequestRepository $friendRequestRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/friendsRequests', name: 'app_friends_requests')]
    public function friendRequest(): Response
    {
        $username = $this->security->getUser()->getUserIdentifier();
        $user     = $this->userRepository->findOneBy(['username' => $username]);

        return $this->render('friend_requests/index.html.twig', [
            'received' => $user->getReceivedFriendRequests()->toArray(),
            'sent'     => $user->getSentFriendRequests()->toArray(),
        ]);
    }

    #[Route('/sendFriendRequest', methods: ['POST'], name: 'app_send_friend_request')]
    public function sendFriendRequest(Request $request): Response
    {
        $requestUserId = (int) $request->request->get('requestUserId');
        $currentUser   = $this->security->getUser();

        $this->friendRequestRepository->setNewFriendRequest($currentUser, $requestUserId);

        $this->addFlash('success', 'Friend Request Sent');
        return $this->redirectToRoute('app_friends_requests');
    }

    // TODO process reqs

    #[Route('/friendRequests/accept', methods: ['POST'], name: 'app_accept_friend_request')]
    public function accept(Request $request): Response
    {
        dd($request->request->get('accept'));
    }

    #[Route('/friendRequests/decline', methods: ['POST'], name: 'app_decline_friend_request')]
    public function decline(Request $request): Response
    {
        dd($request->request->get('decline'));
    }

    #[Route('/friendRequest/cancel', methods: ['POST'], name: 'app_cancel_friend_request')]
    public function cancel(Request $request): Response
    {
        $requestId = (int) $request->request->get('cancel');
        $friendRequest = $this->friendRequestRepository->find($requestId);
        $this->entityManager->remove($friendRequest);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_friends_requests');
    }
}

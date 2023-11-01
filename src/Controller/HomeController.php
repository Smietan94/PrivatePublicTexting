<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route(['/', '/home'], methods: ['GET'], name: 'app_home')]
    public function index(Request $request): Response
    {
        if ($request->query->get('preview')) {
            // TODO display create conversation btn or conversation window
            $friendId     = (int) $request->query->get('q');
            $friend       = $this->userRepository->find($friendId);
            $conversation = $this->conversationRepository->getFriendConversation($this->currentUser, $friend);

            return $this->render('_conversation.html.twig', [
                'friendId'     => $friendId,
                'conversation' => $conversation,
                'pager'        => isset($conversation) ? $this->getMsgPager($request, $friend, $conversation) : null,
                'currentUser'  => $this->currentUser
            ]);
        }

        // collecting all user friends
        $friends = $this->userRepository->getFriendsArray($this->currentUser);

        // getting conversation between user and first friend on list
        $conversation = $this->conversationRepository->getFriendConversation($this->currentUser, $friends[0]);

        return $this->render('home/index.html.twig', [
            'friends'      => $friends,
            'conversation' => $conversation,
            'pager'        => isset($conversation) ? $this->getMsgPager($request, $friends[0], $conversation) : null,
            'currentUser'  => $this->currentUser
        ]);
    }

    #[Route('/startConversation', methods: ['POST'], name: 'app_start_private_conversation')]
    public function startConversation(Request $request): Response
    {
        $friendId = (int) $request->get('friendId');
        $friend   = $this->userRepository->find($friendId);

        $this->checkIfFriends($friend);
        $this->conversationRepository->storeConversation($this->currentUser, $friend);

        return $this->redirectToRoute('app_home');
    }

    private function checkIfFriends(User $friend): bool|Response
    {
        if (!in_array($friend, $this->currentUser->getFriends()->toArray()))
        {
            $this->addFlash('warning', 'You are not friends');
            return $this->redirectToRoute('app_home');
        }
        return true;
    }

    private function getMsgPager(Request $request, User $friend, Conversation $conversation): Pagerfanta
    {
        $queryBuilder = $this->messageRepository->getMessageQuery($this->currentUser, $friend, $conversation);
        $adapter      = new QueryAdapter($queryBuilder);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            10
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\ConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private FormFactoryInterface $formFactory,
        private ChatService $chatService
    ) {
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route(['/', '/home', '/chats'], name: 'app_home')]
    public function index(Request $request): Response
    {
        // collecting all user friends
        $friends = $this->userRepository->getFriendsArray($this->currentUser);

        // getting conversation between user and first friend on list
        $conversation = (count($friends) >= 1) ? $this->conversationRepository->getFriendConversation($this->currentUser, $friends[0]) : null;

        $form       = $this->chatService->processMessage($conversation, $request, 'conversation.priv');
        $searchForm = $this->chatService->createSearchForm();

        return $this->render('chat/index.html.twig', [
            'friends'       => $friends,
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'pager'         => isset($conversation) ? $this->chatService->getMsgPager($request, $conversation, ConversationType::SOLO->toInt()) : null,
            'currentUser'   => $this->currentUser,
            'form'          => $form->createView(),
            'searchForm'    => $searchForm->createView()
        ]);
    }

    #[Route('/chats/{friendId<[0-9]+>}', name: 'app_chat')]
    public function chat(Request $request, int $friendId): Response
    {
        $friend       = $this->userRepository->find($friendId);
        $conversation = $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friend
        );

        if (!$this->checkIfFriends($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You are not friends');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->chatService->processMessage($conversation, $request, 'conversation.priv');
        $searchForm = $this->chatService->createSearchForm();

        return $this->render('chat/index.html.twig', [
            'friends'       => $this->userRepository->getFriendsArray($this->currentUser),
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'pager'         => isset($conversation) ? $this->chatService->getMsgPager($request, $conversation, ConversationType::SOLO->toInt()) : null,
            'currentUser'   => $this->currentUser,
            'form'          => $form->createView(),
            'searchForm'    => $searchForm->createView(),
        ]);
    }

    #[Route('/handleMessage/{conversationId<[0-9]+>}', methods: ['POST'], name: 'handle_message_app')]
    public function handleMessage(Request $request, int $conversationId): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        return $this->render('chat_components/_message.stream.html.twig', [
            'message'       => $jsonData['data'],
            'currentUserId' => $this->currentUser->getId(),
        ]);
    }

    #[Route('/startConversation', methods: ['POST'], name: 'app_start_private_conversation')]
    public function startConversation(Request $request): Response
    {
        // collecting id and friend from db
        $friendId = (int) $request->get('friendId');
        $friend   = $this->userRepository->find($friendId);

        // chceks if friends
        if (!$this->checkIfFriends($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You are not friends');
            return $this->redirectToRoute('app_home');
        }

        // checks if conversation already exists
        if (!$this->checkIfConversationAlreadyExists($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You already have conversation');
            return $this->redirectToRoute('app_home');
        }

        $this->conversationRepository->storeConversation(
            $this->currentUser,
            [$friend],
            ConversationType::SOLO->toInt()
        );

        return $this->redirectToRoute('app_home');
    }

    private function checkIfFriends(User $friend): bool
    {
        // checks if friend in friends list
        return in_array($friend, $this->currentUser->getFriends()->toArray());
    }

    private function checkIfConversationAlreadyExists(User $friend): bool
    {
        $conversation = $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friend
        );

        // checks if conversation already exists
        return $conversation ? false : true;
    }

    #[Route('/chat/search', name: 'app_chat_search')]
    public function processConversationSearch(Request $request): Response
    {
        $searchTerm = $request->query->get('q');

        $friends = $this->userRepository->getFriendsConversationsData(
            $this->currentUser,
            $searchTerm,
        );

        return $this->render('chat/_searchConversationResults.html.twig', [
            'friends' => $friends,
        ]);
    }
}

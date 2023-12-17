<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * ChatController
 */
class ChatController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private FormFactoryInterface $formFactory,
        private ChatService $chatService,
        private EntityManagerInterface $entityManager
    ) {
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * index
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(['/', '/home', '/chats'], name: 'app_home')]
    public function index(Request $request): Response
    {
        // collecting all user friends
        $friends  = $this->userRepository->getFriendsArray($this->currentUser);
        $convData = $this->chatService->getSoloConversationsData($friends, $this->currentUser);

        // Checks if the user has friends to talk to
        if (count($friends) === 0) {
            $this->addFlash(
                'warning',
                'You have no friends to talk'
            );
            return $this->redirectToRoute('app_search_users');
        }

        // getting conversation between user and first friend on list
        $conversation = (count($friends) >= 1) ? $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friends[0]
        ) : null;

        $searchForm  = $this->chatService->createSearchForm();
        $messageForm = $this->processMessageForm($conversation, $request);

        return $this->render('chat/index.html.twig', [
            'friends'       => $friends,
            'convData'      => $convData,
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'currentUserId' => $this->currentUser->getId(),
            'messageForm'   => $messageForm->createView(),
            'searchForm'    => $searchForm->createView(),
            'pager'         => isset($conversation) ? $this->chatService->getMsgPager(
                (int) $request->query->get('page', 1),
                $conversation,
                ConversationType::SOLO->toInt()
            ) : null
        ]);
    }

    /**
     * chat
     *
     * @param  Request $request
     * @param  int $friendId
     * @return Response
     */
    #[Route(
        '/chats/{friendId}',
        name: 'app_chat',
        requirements: ['friendId' => '[0-9]+']
    )]
    public function chat(Request $request, int $friendId): Response
    {
        $friend   = $this->userRepository->find($friendId);
        $friends  = $this->userRepository->getFriendsArray($this->currentUser);
        $convData = $this->chatService->getSoloConversationsData($friends, $this->currentUser);

        // cheks if user exists and if friends with current user
        if (!$friend) {
            $this->addFlash('warning', 'User does not exists');
            return $this->redirectToRoute('app_home');
        }

        if (!$this->checkIfFriends($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You are not friends');
            return $this->redirectToRoute('app_home');
        }

        $conversation = $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friend
        );

        $searchForm  = $this->chatService->createSearchForm();
        $messageForm = $this->processMessageForm($conversation, $request);

        return $this->render('chat/index.html.twig', [
            'friends'       => $friends,
            'convData'      => $convData,
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'currentUserId' => $this->currentUser->getId(),
            'messageForm'   => $messageForm->createView(),
            'searchForm'    => $searchForm->createView(),
            'pager'         => isset($conversation) ? $this->chatService->getMsgPager(
                (int) $request->query->get('page', 1),
                $conversation,
                ConversationType::SOLO->toInt()
            ) : null
        ]);
    }

    /**
     * handleMessage
     *
     * @param  Request $request
     * @param  int $conversationId
     * @return Response
     */
    #[Route(
        '/handleMessage/{conversationId}',
        methods: ['POST'],
        name: 'handle_message_app',
        requirements: ['conversationId' => '[0-9]+']
    )]
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

    /**
     * startConversation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        '/startConversation',
        methods: ['POST'],
        name: 'app_start_private_conversation'
    )]
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

    /**
     * processConversationSearch
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/search', name: 'app_chat_search')]
    public function processConversationSearch(Request $request): Response
    {
        $searchTerm = $request->query->get('q');

        $friends = $this->userRepository->getFriendsConversationsData(
            $this->currentUser,
            $searchTerm,
        );
        $convData = $this->chatService->getSoloConversationsData($friends, $this->currentUser);

        return $this->render('chat/_searchConversationResults.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'friends'       => $friends,
            'convData'      => $convData,
        ]);
    }

    /**
     * checkIfFriends
     *
     * @param  User $friend
     * @return bool
     */
    private function checkIfFriends(User $friend): bool
    {
        // checks if friend in friends list
        return in_array($friend, $this->currentUser->getFriends()->toArray());
    }

    /**
     * checkIfConversationAlreadyExists
     *
     * @param  User $friend
     * @return bool
     */
    private function checkIfConversationAlreadyExists(User $friend): bool
    {
        $conversation = $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friend
        );

        // checks if conversation already exists
        return $conversation ? false : true;
    }

    /**
     * processMessage
     *
     * @param  Conversation $conversation
     * @param  Request $request
     * @return FormInterface
     */
    private function processMessageForm(Conversation $conversation, Request $request): FormInterface
    {
        $messageFormResult = $this->chatService->processMessage(
            $conversation,
            $request,
            'conversation.priv'
        );

        if (isset($messageFormResult['messages'])) {
            $this->processFailedAttachmentUpload($messageFormResult['messages']);
        }

        return $messageFormResult['form'];
    }

    /**
     * processFailedAttachmentUpload
     *
     * @param  array $messages
     * @return void
     */
    private function processFailedAttachmentUpload(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addFlash('turboWarning', $message);
        }
    }
}

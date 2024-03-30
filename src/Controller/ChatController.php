<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\Constant;
use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use App\Service\FileService;
use App\Service\MessageService;
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
        private Security               $security,
        private UserRepository         $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository      $messageRepository,
        private FormFactoryInterface   $formFactory,
        private ChatService            $chatService,
        private MessageService         $messageService,
        private EntityManagerInterface $entityManager
    ) {
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * main solo chat page
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::HOME,
        name: RouteName::APP_HOME
    )]
    public function index(Request $request): Response
    {
        $conversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            ConversationType::SOLO->toInt()
        );
        // getting first conversation on list
        $conversation = isset($conversations[0]) ? $conversations[0] : null;

        // Checks if the user has friends to talk to
        if (count($this->currentUser->getFriends()) === 0) {
            $this->addFlash(
                'warning',
                'You have no friends to talk'
            );
            return $this->redirectToRoute(RouteName::APP_SEARCH_USERS);
        }

        return $this->processResponse(
            $request,
            $conversation,
            $conversations
        );
    }

    /**
     * chat
     *
     * @param  Request $request
     * @param  int     $friendId
     * @return Response
     */
    #[Route(
        RoutePath::SOLO,
        name: RouteName::APP_CHAT,
        requirements: ['conversationId' => '[0-9]+']
    )]
    public function chat(Request $request, int $conversationId): Response
    {
        $conversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            ConversationType::SOLO->toInt()
        );
        $conversation  = $this->conversationRepository->find($conversationId);

        // cheks if user exists and if friends with current user
        if (!$conversation) {
            $this->addFlash('warning', 'Conversation does not exists');
            return $this->redirectToRoute(RouteName::APP_HOME);
        }

        return $this->processResponse(
            $request,
            $conversation,
            $conversations
        );
    }

    /**
     * handles new solo conversation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::START_SOLO_CONVERSATION,
        methods: ['POST'],
        name: RouteName::APP_START_PRIVATE_CONVERSATION
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
        }

        // checks if conversation already exists
        if (!$this->checkIfConversationAlreadyExists($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You already have conversation');
            return $this->redirectToRoute(RouteName::APP_HOME);
        }

        $this->conversationRepository->storeConversation(
            $this->currentUser,
            [$friend],
            ConversationType::SOLO->toInt()
        );

        return $this->redirectToRoute(RouteName::APP_HOME);
    }

    /**
     * process response for index and chat
     *
     * @param  Request        $request
     * @param  ?Conversation  $conversation
     * @param  Conversation[] $conversations
     * @return Response
     */
    private function processResponse(Request $request, ?Conversation $conversation = null, array $conversations): Response
    {
        if (!$this->checkIfUsersConversation($conversation)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You are not mamber of this conversation');
            return $this->redirectToRoute('app_chat');
        }

        $searchForm  = $this->chatService->createSearchForm();
        $messageForm = $this->processMessageForm($conversation, $request);

        return $this->render('chat/chat_solo/index.html.twig', [
            'conversationType' => ConversationType::SOLO->toInt(),
            'conversation'     => $conversation,
            'conversations'    => $conversations,
            'messageForm'      => $messageForm,
            'searchForm'       => $searchForm,
            'pager'            => isset($conversation) ? $this->chatService->getMsgPager(
                (int) $request->query->get('page', 1),
                $conversation
            ) : null
        ]);
    }

    /**
     * checks if in friends list
     *
     * @param  User $friend
     * @return bool
     */
    private function checkIfFriends(User $friend): bool
    {
        // checks if friend in friends list
        return $this->currentUser->getFriends()->contains($friend);
    }

    /**
     * chcecks if conversation already exists
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
     * process message
     *
     * @param  Conversation $conversation
     * @param  Request      $request
     * @return FormInterface
     */
    private function processMessageForm(Conversation $conversation, Request $request): FormInterface
    {
        $messageFormResult = $this->messageService->processMessage(
            $conversation,
            $request,
            Constant::CONVERSATION_PRIV
        );

        if (isset($messageFormResult['messages'])) {
            $this->processFailedAttachmentUpload($messageFormResult['messages']);
        }

        return $messageFormResult['form'];
    }

    /**
     * process any errors during file upload
     *
     * @param  string[] $messages
     * @return void
     */
    private function processFailedAttachmentUpload(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addFlash('turboWarning', $message);
        }
    }

    /**
     * checks if current user is member of conversation
     *
     * @param  Conversation $conversation
     * @return bool
     */
    private function checkIfUsersConversation(Conversation $conversation): bool
    {
        return $this->currentUser->getConversations()->contains($conversation);
    }
}

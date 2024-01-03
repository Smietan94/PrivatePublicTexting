<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Enum\FlashPrefix;
use App\Form\CreateGroupConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use App\Service\MessageAttachmentService;
use App\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatGroupsController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security                 $security,
        private FormFactoryInterface     $formFactory,
        private UserRepository           $userRepository,
        private ConversationRepository   $conversationRepository,
        private MessageRepository        $messageRepository,
        private ChatService              $chatService,
        private MessageService           $messageService,
        private MessageAttachmentService $messageAttachmentService
    ) {
        // collecting logged user
        $userName          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $userName]);
    }

    /**
     * index
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/', name: 'app_chat_groups')]
    public function index(Request $request): Response
    {
        // collecting group conversations
        $groupConversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            ConversationType::GROUP->toInt()
        );
        $groupConversation  = $groupConversations[0] ?? null;

        if (count($groupConversations) == 0) {
            $createGroupForm = $this->formFactory->create(CreateGroupConversationType::class);
            $this->addFlash('warning', 'You have no chat groups yet');
            return $this->render('chat_groups/noConversations.html.twig', [
                'currentUserId'   => $this->currentUser->getId(),
                'createGroupForm' => $createGroupForm->createView(),
            ]);
        }

        return $this->processResponse(
            $request,
            $groupConversation,
            $groupConversations
        );
    }

    /**
     * groupChat
     *
     * @param  Request $request
     * @param  int     $conversationId
     * @return Response
     */
    #[Route(
        '/chat/groups/{conversationId}',
        name: 'app_chat_group',
        requirements: ['conversationId' => '[0-9]+']
    )]
    public function groupChat(Request $request, int $conversationId): Response
    {
        $groupConversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            ConversationType::GROUP->toInt()
        );
        $groupConversation  = $this->conversationRepository->find($conversationId);

        if (!$groupConversation) {
            $this->addFlash('warning', 'chat group does not exists');
            return $this->redirectToRoute('app_chat_groups');
        }

        // checks if user part of conversation group
        if (!$this->chatService->checkIfUserIsMemberOfConversation($groupConversation, $this->currentUser)) {
            $this->addFlash('warning', 'Invalid conversation');
            return $this->redirectToRoute('app_chat_groups');
        }

        return $this->processResponse(
            $request,
            $groupConversation,
            $groupConversations
        );
    }

    /**
     * createChatGroup
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/startGroupConversation', name: 'app_chat_group_create')]
    public function createChatGroup(Request $request): Response
    {
        $groupConversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            ConversationType::GROUP->toInt()
        );
        // creating form
        $createGroupResult = $this->processGroupCreationForm($request);
        $createGroupForm   = $createGroupResult['form'];
        $searchForm        = $this->chatService->createSearchForm();

        if ($createGroupResult['success'] === true) {
            return $this->redirectToRoute('app_chat_group', [
                'conversationId' => $createGroupResult['conversationId'],
            ]);
        } else if ($createGroupResult['success'] === false) {
            return $this->redirectToRoute('app_chat_group_create');
        }

        return $this->render('chat_groups/index.html.twig', [
            'conversationType' => ConversationType::GROUP->toInt(),
            'currentUserId'    => $this->currentUser->getId(),
            'conversations'    => $groupConversations,
            'createGroupForm'  => $createGroupForm->createView(),
            'searchForm'       => $searchForm->createView()
        ]);
    }

    /**
     * handleMessage
     *
     * @param  Request $request
     * @param  int     $conversationId
     * @return Response
     */
    #[Route(
        '/groups/handleMessage/{conversationId}',
        methods: ['POST'],
        name: 'handle_group_message_app',
        requirements: ['conversationId' => '[0-9]+']
    )]
    public function handleMessage(Request $request, int $conversationId): Response
    {
        // collecting message from ajax call
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        // returning data to current user view
        return $this->render('chat_components/_message.stream.html.twig', [
            'message'       => $jsonData['data'],
            'currentUserId' => $this->currentUser->getId(),
        ]);
    }

    /**
     * processConversationNameChangeForm
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        '/chat/groups/changeConversationName',
        methods: ['POST'],
        name:'app_chat_group_change_name'
    )]
    public function processConversationNameChangeForm(Request $request): Response
    {
        $data                = $request->get('change_conversation_name');
        $conversationId      = (int) $data['conversationId'];
        $conversation        = $this->conversationRepository->find($conversationId);
        $newConversationName = $data['conversationName'];

        // check if changing conversation name user is member of conversation
        if ($this->chatService->changeConversationName($conversation, $newConversationName, $this->currentUser)) {
            $this->addFlash('success', 'Successfully changed name');
        } else {
            $this->addFlash('warning', 'You are not part of the conversation');
        }

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversationId,
        ]);
    }

    /**
     * processResponse
     *
     * @param  Request        $request
     * @param  ?Conversation  $groupConversation
     * @param  Conversation[] $groupConversations
     * @return Response
     */
    private function processResponse(Request $request, ?Conversation $groupConversation = null, array $groupConversations): Response
    {
        // creating forms
        [$messageForm, $searchForm, $addUsersForm] = $this->createChatForms($request, $groupConversation);

        return $this->render('chat_groups/index.html.twig', [
            'conversationType' => ConversationType::GROUP->toInt(),
            'currentUserId'    => $this->currentUser->getId(),
            'conversations'    => $groupConversations,
            'conversation'     => $groupConversation ?? null,
            'pager'            => $this->chatService->getMsgPager(
                (int) $request->query->get('page', 1),
                $groupConversation,
                ConversationType::GROUP->toInt()
            ) ?? null,
            'messageForm'  => $messageForm->createView(),
            'searchForm'   => $searchForm->createView(),
            'addUsersForm' => $addUsersForm->createView(),
            'changeConversationNameForm' => $this->chatService->getChangeConversationNameForm(),
            'removeMemberForms'          => $this->chatService->getRemoveConversationMemberForms(
                $groupConversation->getConversationMembers()->toArray(),
                $this->currentUser
            ),
        ]);
    }

    /**
     * createChatForms
     *
     * @param  Request      $request
     * @param  Conversation $conversation
     * @return FormInterface[]
     */
    private function createChatForms(Request $request, Conversation $conversation): array
    {
        // creating basic forms to avoid repeating
        $messageForm  = $this->processMessageForm($conversation, $request);
        $searchForm   = $this->chatService->createSearchForm();
        $addUsersForm = $this->chatService->createAddUsersForm(
            $conversation->getId(),
            $this->currentUser->getId()
        );

        return [
            $messageForm,
            $searchForm,
            $addUsersForm
        ];
    }

    /**
     * processGroupCreationForm
     *
     * @param  Request $request
     * @return array
     */
    public function processGroupCreationForm(Request $request): array
    {
        $creationGroupResult = $this->messageService->processGroupCreation($request, $this->currentUser);

        $this->processFailedAttachmentUpload($creationGroupResult['messages'], 'warning');

        return $creationGroupResult;
    }

    /**
     * processMessage
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
            'conversation.group'
        );

        $this->processFailedAttachmentUpload($messageFormResult['messages'], 'turboWarning');

        return $messageFormResult['form'];
    }

    /**
     * processFailedAttachmentUpload
     *
     * @param  string[] $messages
     * @param  string   $prefix
     * @return void
     */
    private function processFailedAttachmentUpload(array $messages, string $prefix): void
    {
        foreach ($messages as $message) {
            $this->addFlash($prefix, $message);
        }
    }
}

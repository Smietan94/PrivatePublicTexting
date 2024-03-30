<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\Constant;
use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationStatus;
use App\Enum\ConversationType;
use App\Form\CreateGroupConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use App\Service\MessageAttachmentService;
use App\Service\MessageService;
use Exception;
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
        private MessageAttachmentService $messageAttachmentService,
    ) {
        // collecting logged user
        $userName          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $userName]);
    }

    /**
     * main group chat page
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::GROUPS,
        name: RouteName::APP_CHAT_GROUPS
    )]
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

            return $this->render('chat/chat_groups/noConversations.html.twig', [
                'createGroupForm' => $createGroupForm,
            ]);
        }

        return $this->processResponse(
            $request,
            $groupConversation,
            $groupConversations
        );
    }

    /**
     * group chat
     *
     * @param  Request $request
     * @param  int     $conversationId
     * @return Response
     */
    #[Route(
        RoutePath::GROUP,
        name: RouteName::APP_CHAT_GROUP,
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
            return $this->redirectToRoute(RouteName::APP_CHAT_GROUPS);
        }

        // checks if user part of conversation group
        if (!$this->chatService->checkIfUserIsMemberOfConversation($groupConversation, $this->currentUser)) {
            $this->addFlash('warning', 'Invalid conversation');

            return $this->redirectToRoute(RouteName::APP_CHAT_GROUPS);
        }

        return $this->processResponse(
            $request,
            $groupConversation,
            $groupConversations
        );
    }

    /**
     * handles new group conversation creation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::START_GROUP_CONVERSATION,
        name: RouteName::APP_CHAT_GROUP_CREATE
    )]
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
            return $this->redirectToRoute(RouteName::APP_CHAT_GROUP, [
                'conversationId' => $createGroupResult['conversationId'],
            ]);
        } else if ($createGroupResult['success'] === false) {
            return $this->redirectToRoute(RouteName::APP_CHAT_GROUP_CREATE);
        }

        return $this->render('chat/chat_groups/index.html.twig', [
            'conversationType' => ConversationType::GROUP->toInt(),
            'conversations'    => $groupConversations,
            'createGroupForm'  => $createGroupForm,
            'searchForm'       => $searchForm
        ]);
    }

    /**
     * process change of conversation name
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::CHANGE_CONVERSATION_NAME,
        methods: ['POST'],
        name: RouteName::APP_CHAT_GROUP_CHANGE_NAME
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
     * performs soft delete of conversation
     * 
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::REMOVE_CONVERSATION,
        name: RouteName::APP_CHAT_REMOVE_CONVERSATION
    )]
    public function removeConversation(Request $request): Response
    {
        $conversationId = $request->get('remove_conversation')['conversationId'];
        $conversation   = $this->conversationRepository->find($conversationId);

        if ($conversation->getConversationType() === ConversationType::SOLO->toInt()) {
            throw new Exception('You can\'t delete solo conversation');
        }

        if (in_array($conversation, $this->currentUser->getConversations()->toArray())) {
            $this->conversationRepository->conversationSoftDelete($this->currentUser, $conversation);

            $this->addFlash('success', 'Conversation successfully deleted');

        } else {
            $this->addFlash('danger', 'You are not member of this conversation');
        }

        return $this->redirectToRoute(RouteName::APP_CHAT_GROUPS);
    }

    /**
     * process response for index and chat
     *
     * @param  Request        $request
     * @param  ?Conversation  $groupConversation
     * @param  Conversation[] $groupConversations
     * @return Response
     */
    private function processResponse(Request $request, ?Conversation $groupConversation = null, array $groupConversations): Response
    {
        if ($groupConversation->getStatus() === ConversationStatus::DELETED->toInt()) {
            $this->addFlash('warning', 'Invalid conversation');

            return $this->redirectToRoute(RouteName::APP_CHAT_GROUPS);
        }

        // creating forms
        [$messageForm, $searchForm, $addUsersForm] = $this->createChatForms($request, $groupConversation);

        return $this->render('chat/chat_groups/index.html.twig', [
            'conversationType' => ConversationType::GROUP->toInt(),
            'conversations'    => $groupConversations,
            'conversation'     => $groupConversation ?? null,
            'pager'            => $this->chatService->getMsgPager(
                (int) $request->query->get('page', 1),
                $groupConversation
            ) ?? null,
            'messageForm'  => $messageForm,
            'searchForm'   => $searchForm,
            'addUsersForm' => $addUsersForm,
            'removeConversationForm'     => $this->chatService->createRemoveConversationForm(),
            'changeConversationNameForm' => $this->chatService->getChangeConversationNameForm(),
            'removeMemberForms'          => $this->chatService->getRemoveConversationMemberForms(
                $groupConversation->getConversationMembers()->toArray()
            )
        ]);
    }

    /**
     * creates main forms for chat conversation
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
     * process new chat creation form
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
     * message processing
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
            Constant::CONVERSATION_GROUP
        );

        $this->processFailedAttachmentUpload($messageFormResult['messages'], 'turboWarning');

        return $messageFormResult['form'];
    }

    /**
     * process error message if failed attachment upload
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

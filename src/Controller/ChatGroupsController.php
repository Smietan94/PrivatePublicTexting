<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Form\CreateGroupConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
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
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private ChatService $chatService
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
        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);

        if (count($groupConversations) == 0) {
            $createGroupForm = $this->formFactory->create(CreateGroupConversationType::class);
            $this->addFlash('warning', 'You have no chat groups yet');
            return $this->render('chat_groups/noConversations.html.twig', [
                'currentUserId'   => $this->currentUser->getId(),
                'createGroupForm' => $createGroupForm->createView(),
            ]);
        }

        $groupConversation  = $groupConversations[0] ?? null;

        // creating forms
        [$messageForm, $searchForm, $addUsersForm] = $this->createChatForms($request, $groupConversation);

        return $this->render('chat_groups/index.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'conversations' => $groupConversations,
            'conversation'  => $groupConversation,
            'pager'         => $this->chatService->getMsgPager(
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
     * groupChat
     *
     * @param  Request $request
     * @param  int $conversationId
     * @return Response
     */
    #[Route('/chat/groups/{conversationId<[0-9]+>}', name: 'app_chat_group')]
    public function groupChat(Request $request, int $conversationId): Response
    {
        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);
        $groupConversation  = $this->conversationRepository->find($conversationId);

        if (!$groupConversation) {
            $this->addFlash('warning', 'chat group does not exists');
            return $this->redirectToRoute('app_chat_groups');
        }

        // checks if user part of conversation group
        if (!$this->chatService->checkIfUserIsMemberOfConversation($groupConversation, $this->currentUser)) {
            $this->addFlash('warning
            ', 'Invalid conversation');
            return $this->redirectToRoute('app_chat_groups');
        }

        // creating forms
        [$messageForm, $searchForm, $addUsersForm] = $this->createChatForms($request, $groupConversation);

        return $this->render('chat_groups/index.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'conversations' => $groupConversations,
            'conversation'  => $groupConversation ?? null,
            'pager'         => $this->chatService->getMsgPager(
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
     * chatGroupForm
     *
     * @return Response
     */
    #[Route('/chat/groups/createForm', name: 'app_chat_group_form')]
    public function chatGroupForm(): Response
    {
        // creating form
        $createGroupForm = $this->formFactory->create(CreateGroupConversationType::class);

        return $this->render('chat_groups/_createGroupConversationForm.html.twig', [
            'currentUserId'   => $this->currentUser->getId(),
            'createGroupForm' => $createGroupForm->createView(),
        ]);
    }

    /**
     * handleMessage
     *
     * @param  Request $request
     * @param  int $conversationId
     * @return Response
     */
    #[Route('/groups/handleMessage/{conversationId<[0-9]+>}', methods: ['POST'], name: 'handle_group_message_app')]
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
     * processGroupCreation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/startGroupConversation', name: 'app_chat_group_create')]
    public function processGroupCreation(Request $request): Response
    {
        $data = $request->get('create_group_conversation');

        // creating new conversation group
        $conversation = $this->conversationRepository->storeConversation(
            $this->currentUser, 
            // collecting array of conversation members
            array_map(
                fn ($friendId) => $this->userRepository->find((int) $friendId), 
                $data['friends']
            ),
            ConversationType::GROUP->toInt(),
            $data['conversationName'],
        );

        // storing first message in db
        $this->messageRepository->storeMessage(
            $conversation, 
            (int) $data['senderId'], 
            $data['message'],
            false // TODO chek if aatachment added 
        );

        // redirecting to new conversation route
        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversation->getId()
        ]);
    }

    /**
     * processConversationSearch
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/search', name: 'app_chat_group_search')]
    public function processConversationSearch(Request $request): Response
    {
        // processing search call
        $searchTerm = $request->query->get('q');

        // collecting searched group conversations
        $conversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            $searchTerm,
            ConversationType::GROUP->toInt()
        );

        return $this->render('chat_groups/_searchConversationResults.html.twig', [
            'conversations' => $conversations
        ]);
    }

    /**
     * removeUserFromConversation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/removeFromConversation', methods: ['POST'], name: 'app_chat_group_remove_from_conversation')]
    public function removeUserFromConversation(Request $request): Response
    {
        $data           = $request->get('remove_conversation_member');
        $conversationId = (int) $data['conversationId'];
        $memberId       = (int) $data['memberId'];
        $conversation   = $this->conversationRepository->find($conversationId);
        $memberToRm     = $this->userRepository->find($memberId);
        $removedName    = $memberToRm->getUsername();

        // in chat service occurs conversation member check then removes member
        if ($this->chatService->removeMember($conversation, $memberToRm)) {
            $this->addFlash(
                'success',
                sprintf('%s successfully removed from conversation', $removedName)
            );
        } else {
            $this->addFlash(
                'warning',
                sprintf('%s is not part of conversation', $removedName)
            );
        }

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversationId,
        ]);
    }

    /**
     * leaveConversation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/leaveConversation', methods: ['POST'], name: 'app_chat_group_leave_conversation')]
    public function leaveConversation(Request $request): Response
    {
        $data           = $request->get('remove_conversation_member');
        $conversationId = (int) $data['conversationId'];
        $memberId       = (int) $data['memberId'];
        $conversation   = $this->conversationRepository->find($conversationId);
        $memberToRm     = $this->userRepository->find($memberId);

        // in chat service occurs conversation member check then removes current user from chat
        if ($this->chatService->removeMember($conversation, $memberToRm)) {
            $this->addFlash('success', 'You left the conversation');
        } else {
            $this->addFlash('warning', 'You are not part of this conversation');
        }

        return $this->redirectToRoute('app_chat_groups');
    }

    /**
     * addNewConversationMembers
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/addMembers', methods: ['POST'], name: 'app_chat_group_add_members')]
    public function addNewConversationMembers(Request $request): Response
    {
        $data           = $request->get('add_users_to_conversation');
        $conversationId = (int) $data['conversationId'];
        $newMembersIds  = $data['users'];
        $newMembers     = array_map(
            fn ($id) => $this->userRepository->find($id),
            $newMembersIds
        );

        // after adding new members, it returns array of messages 
        $messages = $this->conversationRepository->addNewMember(
            $conversationId,
            $newMembers
        );

        // checks if successfully added users
        if (array_key_exists('success', $messages)) {
            foreach ($messages['success'] as $msg) {
                $this->addFlash('success', $msg);
            }
        }

        // check if any addition failed
        if (array_key_exists('warning', $messages)) {
            foreach ($messages['warning'] as $msg) {
                $this->addFlash('warning', $msg);
            }
        }

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversationId,
        ]);
    }

    /**
     * processConversationNameChangeForm
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chat/groups/changeConversationName', methods: ['POST'], name:'app_chat_group_change_name')]
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
     * createChatForms
     *
     * @param  Request $request
     * @param  Conversation $conversation
     * @return FormInterface[] array
     */
    private function createChatForms(Request $request, Conversation $conversation): array
    {
        // creating basic forms to avoid repeating
        $messageForm = $this->processMessageForm($conversation, $request);

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
            'conversation.group'
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

<?php

declare(strict_types=1);

namespace App\Controller;

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

    #[Route('/chat/groups/', name: 'app_chat_groups')]
    public function index(Request $request): Response
    {
        $currentUserId = $this->currentUser->getId();
        // collecting group conversations
        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);
        $groupConversation  = isset($groupConversations) ? $groupConversations[0] : null;

        $form = $this->chatService->processMessage($groupConversation, $request, 'conversation.group');

        $searchForm = $this->chatService->createSearchForm();

        $addUsersForm = $this->chatService->createAddUsersForm($groupConversation->getId(), $currentUserId);

        return $this->render('chat_groups/index.html.twig', [
            'currentUserId' => $currentUserId,
            'conversations' => $groupConversations,
            'conversation'  => $groupConversation,
            'pager'         => isset($groupConversation) ? $this->chatService->getMsgPager($request, $groupConversation, ConversationType::GROUP->toInt()) : null,
            'form'          => $form->createView(),
            'searchForm'    => $searchForm->createView(),
            'changeConversationNameForm' => $this->chatService->getChangeConversationNameForm(),
            'removeMemberForms'          => $this->chatService->getRemoveConversationMemberForms($groupConversation->getConversationMembers()->toArray(), $this->currentUser),
            'addUsersForm'               => $addUsersForm->createView()
        ]);
    }

    #[Route('/chat/groups/{conversationId<[0-9]+>}', name: 'app_chat_group')]
    public function groupChat(Request $request, int $conversationId): Response
    {
        $currentUserId = $this->currentUser->getId();

        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);
        $groupConversation  = $this->conversationRepository->find($conversationId);

        if (!$this->chatService->checkIfUserIsMemberOfConversation($groupConversation, $this->currentUser)) {
            $this->addFlash('warning
            ', 'Invalid conversation');
            return $this->redirectToRoute('app_chat_groups');
        }

        $form = $this->chatService->processMessage($groupConversation, $request, 'conversation.group');

        $searchForm = $this->chatService->createSearchForm();

        $addUsersForm = $this->chatService->createAddUsersForm($groupConversation->getId(), $currentUserId);

        return $this->render('chat_groups/index.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'conversations' => $groupConversations,
            'conversation'  => $groupConversation ?? null,
            'pager'         => isset($groupConversation) ? $this->chatService->getMsgPager($request, $groupConversation, ConversationType::GROUP->toInt()) : null,
            'form'          => $form->createView(),
            'searchForm'    => $searchForm->createView(),
            'changeConversationNameForm' => $this->chatService->getChangeConversationNameForm(),
            'removeMemberForms'          => $this->chatService->getRemoveConversationMemberForms($groupConversation->getConversationMembers()->toArray(), $this->currentUser),
            'addUsersForm'               => $addUsersForm->createView()
        ]);
    }

    #[Route('/chat/groups/createForm', name: "app_chat_group_form")]
    public function chatGroupForm(): Response
    {
        // creating form
        $form = $this->formFactory->create(CreateGroupConversationType::class);

        return $this->render('chat_groups/_createGroupConversationForm.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'form'          => $form->createView(),
        ]);
    }

    #[Route('/groups/handleMessage/{conversationId<[0-9]+>}', methods: ['POST'], name: 'handle_group_message_app')]
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

    #[Route('/chat/groups/startGroupConversation', name: 'app_chat_group_create')]
    public function processGroupCreation(Request $request): Response
    {
        $data = $request->get('create_group_conversation');

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

        $this->messageRepository->storeMessage(
            $conversation, 
            (int) $data['senderId'], 
            $data['message']
        );

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversation->getId()
        ]);
    }

    #[Route('/chat/groups/search', name: 'app_chat_group_search')]
    public function processConversationSearch(Request $request): Response
    {
        $searchTerm = $request->query->get('q');

        $conversations = $this->conversationRepository->getConversations(
            $this->currentUser,
            $searchTerm,
            ConversationType::GROUP->toInt()
        );

        return $this->render('chat_groups/_searchConversationResults.html.twig', [
            'conversations' => $conversations
        ]);
    }

    #[Route('/chat/groups/removeFromConversation', methods: ['POST'], name: 'app_chat_group_remove_from_conversation')]
    public function removeUserFromConversation(Request $request): Response
    {
        $data           = $request->get('remove_conversation_member');
        $conversationId = (int) $data['conversationId'];
        $memberId       = (int) $data['memberId'];
        $conversation   = $this->conversationRepository->find($conversationId);
        $memberToRm     = $this->userRepository->find($memberId);
        $removedName    = $memberToRm->getUsername();

        if ($this->chatService->removeMember($conversation, $memberToRm)) {
            $this->addFlash('success', sprintf('%s successfully removed from conversation', $removedName));
        } else {
            $this->addFlash('warning', sprintf('%s is not part of conversation', $removedName));
        }

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversationId,
        ]);
    }

    #[Route('/chat/groups/leaveConversation', methods: ['POST'], name: 'app_chat_group_leave_conversation')]
    public function leaveConversation(Request $request): Response
    {
        $data           = $request->get('remove_conversation_member');
        $conversationId = (int) $data['conversationId'];
        $memberId       = (int) $data['memberId'];
        $conversation   = $this->conversationRepository->find($conversationId);
        $memberToRm     = $this->userRepository->find($memberId);

        if ($this->chatService->removeMember($conversation, $memberToRm)) {
            $this->addFlash('success', 'You left the conversation');
        } else {
            $this->addFlash('warning', 'You are not part of this conversation');
        }

        return $this->redirectToRoute('app_chat_groups');
    }

    // TODO process adding new group members
    #[Route('/chat/groups/addMembers', methods: ['POST'], name: 'app_chat_group_add_members')]
    public function addNewConversationMembers(Request $request): Response
    {
        $data           = $request->get('add_users_to_conversation');
        $conversationId = (int) $data['conversationId'];
        $newMembersIds  = $data['users'];
        $newMembers     = array_map(fn ($id) => $this->userRepository->find($id), $newMembersIds);

        $messages = $this->conversationRepository->addNewMember($conversationId, $newMembers);

        if (array_key_exists('success', $messages)) {
            foreach ($messages['success'] as $msg) {
                $this->addFlash('success', $msg);
            }
        }

        if (array_key_exists('warning', $messages)) {
            foreach ($messages['warning'] as $msg) {
                $this->addFlash('warnig', $msg);
            }
        }

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversationId,
        ]);
    }

    #[Route('/chat/groups/changeConversationName', methods: ['POST'], name:'app_chat_group_change_name')]
    public function processConversationNameChangeForm(Request $request): Response
    {
        $data                = $request->get('change_conversation_name');
        $conversationId      = (int) $data['conversationId'];
        $conversation        = $this->conversationRepository->find($conversationId);
        $newConversationName = $data['conversationName'];

        if ($this->chatService->changeConversationName($conversation, $newConversationName, $this->currentUser)) {
            $this->addFlash('success', 'Successfully changed name');
        } else {
            $this->addFlash('warning', 'You are not part of the conversation');
        }

        return $this->redirectToRoute('app_chat_group', [
            'conversationId' => $conversationId,
        ]);
    }
}

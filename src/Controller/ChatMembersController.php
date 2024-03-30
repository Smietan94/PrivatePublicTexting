<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatMembersController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security               $security,
        private ConversationRepository $conversationRepository,
        private UserRepository         $userRepository,
        private ChatService            $chatService,
    ) {
        // collecting logged user
        $userName          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $userName]);
    }

    /**
     * handles removing user from conversation
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::REMOVE_FROM_CONVERSATION,
        methods: ['POST'],
        name: RouteName::APP_CHAT_GROUP_REMOVE_FROM_CONVERSATION
    )]
    public function removeUserFromConversation(Request $request): Response
    {
        $data           = $request->get('remove_conversation_member');
        $conversationId = (int) $data['conversationId'];
        $memberId       = (int) $data['memberId'];
        $conversation   = $this->conversationRepository->find($conversationId);
        $memberToRm     = $this->userRepository->find($memberId);
        $removedName    = $memberToRm->getUsername();

        // in chat service occurs conversation member check then removes member
        if ($this->chatService->removeMember(NotificationType::REMOVED_FROM_CONVERSATION, $conversation, $memberToRm, $this->currentUser)) {
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

        return $this->redirectToRoute(RouteName::APP_CHAT_GROUP, [
            'conversationId' => $conversationId,
        ]);
    }

    /**
     * handles conversation left by current user
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::LEAVE_CONVERSATION,
        methods: ['POST'],
        name: RouteName::APP_CHAT_GROUP_LEAVE_CONVERSATION
    )]
    public function leaveConversation(Request $request): Response
    {
        $data           = $request->get('remove_conversation_member');
        $conversationId = (int) $data['conversationId'];
        $conversation   = $this->conversationRepository->find($conversationId);

        // in chat service occurs conversation member check then removes current user from chat
        if ($this->chatService->removeMember(NotificationType::LEFT_THE_CONVERSATION, $conversation, $this->currentUser)) {
            $this->addFlash('success', 'You left the conversation');
        } else {
            $this->addFlash('warning', 'You are not part of this conversation');
        }

        return $this->redirectToRoute(RouteName::APP_CHAT_GROUPS);
    }

    /**
     * handles new conversation members
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::ADD_MEMBERS,
        methods: ['POST'],
        name: RouteName::APP_CHAT_GROUP_ADD_MEMBERS
    )]
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
            $this->currentUser,
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

        return $this->redirectToRoute(RouteName::APP_CHAT_GROUP, [
            'conversationId' => $conversationId,
        ]);
    }
}
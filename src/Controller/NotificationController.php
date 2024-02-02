<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security               $security,
        private UserRepository         $userRepository,
        private ConversationRepository $conversationRepository,
        private ChatService            $chatService
    ) {
        // collecting current user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * processMessagePreview
     *
     * @param  Request  $request
     * @return Response
     */
    #[Route('/chats/messagePreview', name: 'app_chat_message_preview')]   
    public function processMessagePreview(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        return $this->render('chat_components/_messagePreview.html.twig', [
            'data' => $jsonData['data']
        ]);
    }

    /**
     * processConversationLabelPreview
     *
     * @param  Request  $request
     * @return Response
     */
    #[Route('/chats/processConversationLabel', name: 'app_chat_group_conversation_label')]
    public function processConversationLabelPreview(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $converastionId = (int) $jsonData['data'];
        $conversation   = $this->conversationRepository->find($converastionId);
        $lastMessage    = $conversation->getLastMessage();

        return $this->render('chat_groups/_conversationLabel.html.twig', [
            'data' => [
                'conversationId'   => $converastionId,
                'conversationName' => $conversation->getName(),
                'message'          => $lastMessage->getMessage(),
                'senderId'         => $lastMessage->getSenderId()
            ]
        ]);
    }

    /**
     * createMercureEventSourceScriptTagForConversationLabel
     *
     * @param  Request  $request
     * @return Response
     */
    #[Route('/chats/groupChat/processEventSourceScriptTag', name: 'app_chat_process_event_source_tag')]
    public function createMercureEventSourceScriptTagForConversationLabel(Request $request): Response
    {
        // collecting message from ajax call
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        return $this->render('chat_components/_mercureEventSourceScriptTag.html.twig', [
            'createdGroupTopics' => $jsonData['data']
        ]);
    }

    /**
     * redirectRemovedUser
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chats/redirectRemovedUser', name: 'app_chat_redeirect_removed_user')]
    public function redirectRemovedUser(Request $request): Response
    {
        // collecting data from ajax call
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $removedUserId  = (int) $jsonData['data']['removedUserId'];
        $conversationId = (int) $jsonData['data']['conversationId'];

        $conversation = $this->conversationRepository->find($conversationId);

        if ($removedUserId === $this->currentUser->getId()) {
            $this->addFlash('success', sprintf('You\'ve been removed from %s conversation.', $conversation->getName()));
        }

        return new JsonResponse([
            'currentUserId'  => $this->currentUser->getId(),
            'removedUserId'  => $removedUserId,
            'conversationId' => $conversationId
        ]);
    }

    /**
     * processConversationRemove
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chats/processConversationRemove', name: 'app_chat_peocess_conversation_remove')]
    public function processConversationRemove(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $conversation = $this->conversationRepository->find($jsonData['data']);

        $this->addFlash('warning', sprintf('Conversation %s has been deleted', $conversation->getName()));

        return new JsonResponse();
    }

    /**
     * processConversationMembersList
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/chats/group/updateMembersList', name: 'app_chat_update_members_list')]
    public function processConversationMembersList(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $conversation      = $this->conversationRepository->find($jsonData['data']);
        $removeMemberForms = $this->chatService->getRemoveConversationMemberForms(
            $conversation->getConversationMembers()->toArray()
        );

        return $this->render('chat_groups/_conversationMembersList.html.twig', [
            'conversation'      => $conversation,
            'removeMemberForms' => $removeMemberForms,
            'currentUserId'     => $this->currentUser->getId()
        ]);
    }

    /**
     * setActivityStatus
     *
     * @param  Request $request
     * @return Response
     */
    #[Route('/setActivityStatus', name: 'app_set_activity_status')]
    public function setActivityStatus(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $this->userRepository->changeActivityStatus($this->currentUser, $jsonData['data']);

        return new JsonResponse();
    }
}
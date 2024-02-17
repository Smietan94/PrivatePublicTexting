<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\NotificationRepository;
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
        private NotificationRepository $notificationRepository,
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
    #[Route(
        RoutePath::MESSAGE_PREVIEW,
        name: RouteName::APP_CHAT_MESSAGE_PREVIEW
    )]
    public function processMessagePreview(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $conversation = $this->conversationRepository->find((int) $jsonData['conversationId']);
        $lastMessage  = $conversation->getLastMessage();

        return $this->render('chat_components/_messagePreview.html.twig', [
            'data' => [
                'senderId' => $lastMessage->getSenderId(),
                'message'  => substr($lastMessage->getMessage(), 0, 20)
            ]
        ]);
    }

    /**
     * processConversationLabelPreview
     *
     * @param  Request  $request
     * @return Response
     */
    #[Route(
        RoutePath::PROCESS_CONVERSATION_LABEL,
        name: RouteName::APP_CHAT_GROUP_CONVERSATION_LABEL
    )]
    public function processConversationLabelPreview(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $converastionId = (int) $jsonData['conversationId'];
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
    #[Route(
        RoutePath::PROCESS_EVENT_SOURCE_SCRIPT_TAG,
        name: RouteName::APP_CHAT_PROCESS_EVENT_SOURCE_TAG
    )]
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
    #[Route(
        RoutePath::REDIRECT_REMOVED_USER,
        name: RouteName::APP_CHAT_REDEIRECT_REMOVED_USER
    )]
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
    #[Route(
        RoutePath::PROCESS_CONVERSATION_REMOVE,
        name: RouteName::APP_CHAT_PEOCESS_CONVERSATION_REMOVE
    )]
    public function processConversationRemove(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $conversation = $this->conversationRepository->find($jsonData['removedConversationId']);

        $this->addFlash('warning', sprintf('Conversation %s has been deleted', $conversation->getName()));

        return new JsonResponse([
            'response' => true
        ]);
    }

    /**
     * processConversationMembersList
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::UPDATE_MEMBERS_LIST,
        name: RouteName::APP_CHAT_UPDATE_MEMBERS_LIST
    )]
    public function processConversationMembersList(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $conversation      = $this->conversationRepository->find($jsonData['conversationId']);
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
    #[Route(
        RoutePath::SET_ACTIVITY_STATUS,
        name: RouteName::APP_SET_ACTIVITY_STATUS
    )]
    public function setActivityStatus(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $this->userRepository->changeActivityStatus($this->currentUser, $jsonData['userActivityStatusCode']);

        return new JsonResponse([
            'response' => true
        ]);
    }

    /**
     * getUnseenNotificationsNumber
     * 
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::GET_UNSEEN_NOTIFICATIONS_NUMBER,
        name: RouteName::APP_GET_UNSEEN_NOTIFICATIONS_NUMBER
    )]
    public function getUnseenNotificationsNumber(Request $request): Response
    {
        return $this->render('_navDropDown.html.twig');
    }

    /**
     * reloadNotificationsModal
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::RELOAD_NOTIFICATIONS_MODAL,
        name: RouteName::APP_RELOAD_NOTIFICATIONS_MODAL
    )]
    public function reloadNotificationsModal(Request $request): Response
    {
        return $this->render('_notificationsModal.html.twig');
    }
}
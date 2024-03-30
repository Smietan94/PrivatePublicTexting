<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\Constant;
use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\ConversationRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private RequestStack           $requestStack,
        private Security               $security,
        private UserRepository         $userRepository,
        private ConversationRepository $conversationRepository,
        private NotificationRepository $notificationRepository,
        private NotificationService    $notificationService,
        private ChatService            $chatService
    ) {
        // collecting current user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * update message preview
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

        return $this->render('chat/chat_components/_messagePreview.html.twig', [
            'data' => [
                'senderId' => $lastMessage->getSenderId(),
                'message'  => substr($lastMessage->getMessage(), 0, 20)
            ]
        ]);
    }

    /**
     * updates conversation label
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

        return $this->render('chat/chat_groups/_conversationLabel.html.twig', [
            'data' => [
                'conversationId'   => $converastionId,
                'conversationName' => $conversation->getName(),
                'message'          => $lastMessage->getMessage(),
                'senderId'         => $lastMessage->getSenderId()
            ]
        ]);
    }

    /**
     * create new conversation mercure script tag
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

        return $this->render('chat/chat_components/_mercureEventSourceScriptTag.html.twig', [
            'createdGroupTopics' => $jsonData['data']
        ]);
    }

    /**
     * process removed removed from conversation user redirection
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
     * process conversation remove
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
     * updates conversation members list
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

        return $this->render('chat/chat_groups/_conversationMembersList.html.twig', [
            'conversation'      => $conversation,
            'removeMemberForms' => $removeMemberForms,
            'currentUserId'     => $this->currentUser->getId()
        ]);
    }

    /**
     * set user activity status
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
     * get number of unseen notifications
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
        return $this->render('nav_dropdown/_navDropDown.html.twig');
    }

    /**
     * reloads noifications modal
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
        return $this->processNotificationsModal(
            $request,
            '_notificationsList.html.twig'
        );
    }

    /**
     * rebders notification modal
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::RENDER_NOTIFICATIONS_MODAL,
        name: RouteName::APP_RENDER_NOTIFICATIONS_MODAL
    )]
    public function renderNotificationsModal(Request $request): Response
    {
        return $this->processNotificationsModal(
            $request,
            '_notificationsModal.html.twig',
            true
        );
    }

    /**
     * updates notification display status
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::SET_NOTIFICATION_DISPLAY_STATUS,
        name: RouteName::APP_SET_NOTIFICATION_DISPLAY_STATUS
    )]
    public function setNotificationDisplayStatus(Request $request): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $notification = $this->notificationRepository->setNotificationDisplayStatus((int) $jsonData['notificationId']);

        return new JsonResponse([
            'notificationType' => $notification->getNotificationType()
        ]);
    }

    /**
     * reloads notification filters list
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::RELOAD_NOTIFICATIONS_FILTERS_LIST,
        name: RouteName::APP_RELOAD_NOTIFICATIONS_FILTERS_LIST
    )]
    public function reloadNotificationsFiltersList(Request $request): Response
    {
        return $this->render('nav_dropdown/notifications_modal/_notificationsFilterDropdown.html.twig', [
            'notificationTypes' => NotificationType::cases()
        ]);
    }

    /**
     * process notification modal
     *
     * @param  Request $request
     * @param  string  $fileName
     * @param  bool    $render
     * @return Response
     */
    private function processNotificationsModal(Request $request, string $fileName, bool $render=false): Response
    {
        $session = $this->requestStack->getSession();

        if (!$request->query->get('page')) {
            $this->handleSortByDate($request, $session);
            $this->handleNotificationFilter($request, $session);
            $this->resetFilters($request, $session);
        }

        $this->setSessionArgumentsIfNotSat($session);

        return $this->render(sprintf('nav_dropdown/notifications_modal/%s', $fileName), [
            'notificationTypes' => $render ? NotificationType::cases(): null,
            'notifications'     => $this->notificationService->getNotificationsPager(
                (int) $request->query->get('page', 1),
                $this->currentUser,
                $session->get(Constant::NOTIFICATIONS_ORDER_BY_DATE),
                $session->get(Constant::NOTIFICATIONS_TYPES_TO_DISPLAY)
            )
        ]);
    }

    /**
     * reset notifications filters
     *
     * @param  Request $request
     * @param  Session $session
     * @return void
     */
    private function resetFilters(Request $request, Session $session)
    {
        if ($request->query->get('resetNotificationsFilters')) {
            $session->set(Constant::NOTIFICATIONS_ORDER_BY_DATE, 'DESC');
            $session->set(Constant::NOTIFICATIONS_TYPES_TO_DISPLAY, []);
        }
    }

    /**
     * set session arguments if not sat
     *
     * @param  Session $session
     * @return void
     */
    private function setSessionArgumentsIfNotSat(Session $session)
    {
        if (!$session->get(Constant::NOTIFICATIONS_ORDER_BY_DATE)) {
            $session->set(Constant::NOTIFICATIONS_ORDER_BY_DATE, 'DESC');
        }

        if (!$session->get(Constant::NOTIFICATIONS_TYPES_TO_DISPLAY)) {
            $session->set(Constant::NOTIFICATIONS_TYPES_TO_DISPLAY, []);
        }
    }

    /**
     * handles notifications sort order by date
     *
     * @param  Request $request
     * @param  Session $session
     * @return void
     */
    private function handleSortByDate(Request $request, Session $session)
    {
        if ($request->query->get('orderByDate')) {
            $order = $request->query->get('order');
            $session->set(Constant::NOTIFICATIONS_ORDER_BY_DATE, $order);
        }
    }

    /**
     * handle notifications filtering
     *
     * @param  Request $request
     * @param  Session $session
     * @return void
     */
    private function handleNotificationFilter(Request $request, Session $session)
    {
        if ($request->query->get('notificationTypeFilter')) {
            $notificationType   = (int) $request->query->get('notificationType');
            $notificationsTypes = $session->get(Constant::NOTIFICATIONS_TYPES_TO_DISPLAY);

            if (in_array($notificationType, $notificationsTypes)) {
                $notificationTypeToUnset = array_search($notificationType, $notificationsTypes);
                unset($notificationsTypes[$notificationTypeToUnset]);
            } else {
                array_push($notificationsTypes, $notificationType);
            }

            $session->set(Constant::NOTIFICATIONS_TYPES_TO_DISPLAY, $notificationsTypes);
        }
    }
}
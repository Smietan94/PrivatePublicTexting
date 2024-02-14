<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Constants\RouteName;
use App\Entity\Constants\RoutePath;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatComponentController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security               $security,
        private ConversationRepository $conversationRepository,
        private UserRepository         $userRepository,
        private MessageRepository      $messageRepository
    ) {
        // collecting logged user
        $userName          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $userName]);
    }

    /**
     * processConversationSearch
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::CHAT_SEARCH,
        name: RouteName::APP_CHAT_SEARCH
    )]
    public function processConversationSearch(Request $request): Response
    {
        $searchTerm       = $request->query->get('q');
        $conversationType = (int) $request->query->get('type');

        $conversations = $this->conversationRepository->getSearchedConversations(
            $this->currentUser,
            $searchTerm,
            $conversationType
        );

        $templatePrefix = match ($conversationType) {
            ConversationType::SOLO->toInt()  => 'chat',
            ConversationType::GROUP->toInt() => 'chat_groups'
        };

        return $this->render(sprintf('%s/_searchConversationResults.html.twig', $templatePrefix), [
            'conversations'        => $conversations,
            'activeConversationId' => $request->query->get('convId')
        ]);
    }

        /**
     * handleMessage
     *
     * @param  Request $request
     * @return Response
     */
    #[Route(
        RoutePath::HANDLE_MESSAGE,
        methods: ['POST'],
        name: RouteName::APP_HANDLE_MESSAGE
    )]
    public function handleMessage(Request $request): Response
    {
        // collecting message from ajax call
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        $message = $this->messageRepository->find($jsonData['messageId']);

        // returning data to current user view
        return $this->render('chat_components/_message.stream.html.twig', [
            'message' => $message,
        ]);
    }
}
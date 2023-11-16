<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Form\MessageType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private FormFactoryInterface $formFactory,
        private HubInterface $hub,
    ) {
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route(['/', '/home', '/chats'], name: 'app_home')]
    public function index(Request $request): Response
    {
        // collecting all user friends
        $friends = $this->userRepository->getFriendsArray($this->currentUser);

        // getting conversation between user and first friend on list
        $conversation = (count($friends) >= 1) ? $this->conversationRepository->getFriendConversation($this->currentUser, $friends[0]) : null;

        $form = $this->processMessage($conversation, $request);

        return $this->render('chat/index.html.twig', [
            'friends'       => $friends,
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'pager'         => isset($conversation) ? $this->getMsgPager($request, $conversation) : null,
            'currentUser'   => $this->currentUser,
            'form'          => $form->createView(),
        ]);
    }

    #[Route('/chats/{friendId<[0-9]+>}', name: 'app_chat')]
    public function chat(Request $request, int $friendId): Response
    {
        $friend       = $this->userRepository->find($friendId);
        $conversation = $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friend
        );

        if (!$this->checkIfFriends($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You are not friends');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->processMessage($conversation, $request);

        return $this->render('chat/index.html.twig', [
            'friends'       => $this->userRepository->getFriendsArray($this->currentUser),
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'pager'         => isset($conversation) ? $this->getMsgPager($request, $conversation) : null,
            'currentUser'   => $this->currentUser,
            'form'          => $form->createView()
        ]);
    }

    #[Route('/handleMessage/{conversationId<[0-9]+>}', methods: ['POST'], name: 'handle_message_app')]
    public function handleMessage(Request $request, int $conversationId): Response
    {
        $jsonData = json_decode(
            $request->getContent(),
            true
        );

        return $this->render('_message.stream.html.twig', [
            'message'       => $jsonData['data'],
            'currentUserId' => $this->currentUser->getId(),
        ]);
    }

    #[Route('/startConversation', methods: ['POST'], name: 'app_start_private_conversation')]
    public function startConversation(Request $request): Response
    {
        // collecting id and friend from db
        $friendId = (int) $request->get('friendId');
        $friend   = $this->userRepository->find($friendId);

        // chceks if friends
        if (!$this->checkIfFriends($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You are not friends');
            return $this->redirectToRoute('app_home');
        }

        // checks if conversation already exists
        if (!$this->checkIfConversationAlreadyExists($friend)) {
            // if not, then flashes inforamation
            $this->addFlash('warning', 'You already have conversation');
            return $this->redirectToRoute('app_home');
        }

        $this->conversationRepository->storeConversation(
            $this->currentUser,
            [$friend],
            ConversationType::SOLO->toInt()
        );

        return $this->redirectToRoute('app_home');
    }

    private function checkIfFriends(User $friend): bool
    {
        // checks if friend in friends list
        return in_array($friend, $this->currentUser->getFriends()->toArray());
    }

    private function checkIfConversationAlreadyExists(User $friend): bool
    {
        $conversation = $this->conversationRepository->getFriendConversation(
            $this->currentUser,
            $friend
        );

        // checks if conversation already exists
        return $conversation ? false : true;
    }

    private function getMsgPager(Request $request, Conversation $conversation): Pagerfanta
    {
        // gets query which prepering all messages from conversation
        $queryBuilder = $this->messageRepository->getMessageQuery(
            $conversation,
            ConversationType::SOLO->toInt()
        );

        $adapter = new QueryAdapter($queryBuilder);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            10
        );
    }

    private function processMessage(?Conversation $conversation = null, Request $request): FormInterface
    {
        $form      = $this->formFactory->create(MessageType::class);
        $emptyForm = $this->formFactory->create(MessageType::class);

        $form->handleRequest($request);

        // checking if use have any conversations
        if (!$conversation) {
            return $form;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data   = $form->getData();

            // creating mercure update
            $update = new Update(
                'conversation.priv' . $conversation->getId(), // topic
                json_encode([
                    'message' => $data,
                ]),
                true
            );

            // publishing mercure update
            $this->hub->publish($update);

            // saving message in db
            $this->messageRepository->storeMessage(
                $conversation,
                $data['senderId'],
                $data['message']
            );

            return $emptyForm;
        }

        return $form;
    }
}

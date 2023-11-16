<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Form\CreateGroupConversationType;
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

class ChatGroupsController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private HubInterface $hub,
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository
    ) {
        // collecting logged user
        $userName          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $userName]);
    }

    #[Route('/chat/groups/', name: 'app_chat_groups')]
    public function index(Request $request, ?int $conversationId = null): Response
    {
        // collecting group conversations
        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);
        $groupConversation  = isset($groupConversations) ? $groupConversations[0] : null;

        $form = $this->processMessage($groupConversation, $request);

        return $this->render('chat_groups/index.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'conversations' => $groupConversations,
            'conversation'  => $groupConversation,
            'pager'         => isset($groupConversation) ? $this->getMsgPager($request, $groupConversation) : null,
            'form'          => $form
        ]);
    }

    #[Route('/chat/groups/{conversationId<[0-9]+>}', name: 'app_chat_group')]
    public function groupChat(Request $request, int $conversationId): Response
    {
        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);
        $groupConversation  = $this->conversationRepository->find($conversationId);

        $form = $this->processMessage($groupConversation, $request);

        return $this->render('chat_groups/index.html.twig', [
            'currentUserId' => $this->currentUser->getId(),
            'conversations' => $groupConversations,
            'conversation'  => $groupConversation ?? null,
            'pager'         => isset($groupConversation) ? $this->getMsgPager($request, $groupConversation) : null,
            'form'          => $form
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

        return $this->render('_message.stream.html.twig', [
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

    private function getMsgPager(Request $request, Conversation $conversation): Pagerfanta
    {
        // gets query which prepering all messages from conversation
        $queryBuilder = $this->messageRepository->getMessageQuery(
            $conversation,
            ConversationType::GROUP->toInt()
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
                'conversation.group' . $conversation->getId(), // topic
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

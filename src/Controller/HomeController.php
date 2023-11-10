<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
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

class HomeController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private ConversationRepository $conversationRepository,
        private MessageRepository $messageRepository,
        private FormFactoryInterface $formFactory
    ) {
        // collecting logged user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

    #[Route(['/', '/home', '/chats'], name: 'app_home')]
    public function index(Request $request, HubInterface $hub): Response
    {
        $form      = $this->formFactory->create(MessageType::class);
        $emptyForm = clone $form;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $hub->publish(new Update(
                'chat',
                $this->renderView('_message.stream.html.twig', [
                    'message' => $data
                ])
            ));

            $form = $emptyForm;
        }

        if ($request->query->get('preview')) {
            // display create conversation btn or conversation window
            return $this->processConversationChoice($request, $form);
        }

        // collecting all user friends
        $friends = $this->userRepository->getFriendsArray($this->currentUser);

        // getting conversation between user and first friend on list
        $conversation = $this->conversationRepository->getFriendConversation($this->currentUser, $friends[0]);

        return $this->render('home/index.html.twig', [
            'friends'       => $friends,
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'pager'         => isset($conversation) ? $this->getMsgPager($request, $friends[0], $conversation) : null,
            'currentUser'   => $this->currentUser,
            'form'          => $form->createView(),
        ]);
    }

    #[Route('/chats/{friendId<[0-9]+>}', name: 'app_chat')]
    public function chat(Request $request, int $friendId): Response
    {
        $friend       = $this->userRepository->find($friendId);
        $conversation = $this->conversationRepository->getFriendConversation($this->currentUser, $friend);

        return $this->render('home/index.html.twig', [
            'friends'       => $this->userRepository->getFriendsArray($this->currentUser),
            'conversation'  => $conversation,
            'conversations' => $this->currentUser->getConversations()->toArray(),
            'pager'         => isset($conversation) ? $this->getMsgPager($request, $friend, $conversation) : null,
            'currentUser'   => $this->currentUser
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

        $this->conversationRepository->storeConversation($this->currentUser, $friend);

        return $this->redirectToRoute('app_home');
    }

    private function processConversationChoice(Request $request, FormInterface $form): Response
    {
        // gets chosen friend id, then collecting friend and conversation
        $friendId     = (int) $request->query->get('q');
        $friend       = $this->userRepository->find($friendId);
        $conversation = $this->conversationRepository->getFriendConversation($this->currentUser, $friend);

        return $this->render('home/_conversation.html.twig', [
            'friendId'     => $friendId,
            'conversation' => $conversation,
            'pager'        => isset($conversation) ? $this->getMsgPager($request, $friend, $conversation) : null,
            'currentUser'  => $this->currentUser,
            'form'         => $form->createView()
        ]);
    }

    private function checkIfFriends(User $friend): bool
    {
        // checks if friend in friends list
        return in_array($friend, $this->currentUser->getFriends()->toArray()) ? true : false;
    }

    private function checkIfConversationAlreadyExists(User $friend): bool
    {
        $conversation = $this->conversationRepository->getFriendConversation($this->currentUser, $friend);

        // checks if conversation already exists
        return $conversation ? false : true;
    }

    private function getMsgPager(Request $request, User $friend, Conversation $conversation): Pagerfanta
    {
        // gets query which prepering all messages from conversation
        $queryBuilder = $this->messageRepository->getMessageQuery($this->currentUser, $friend, $conversation);
        $adapter      = new QueryAdapter($queryBuilder);

        return Pagerfanta::createForCurrentPageWithMaxPerPage(
            $adapter,
            (int) $request->query->get('page', 1),
            10
        );
    }
}

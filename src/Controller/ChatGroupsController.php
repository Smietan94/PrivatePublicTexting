<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CreateGroupConversationType;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
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
    ) {
        // collecting logged user
        $userName          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $userName]);
    }

    #[Route('/chat/groups', name: 'app_chat_groups')]
    public function index(Request $request): Response
    {
        // collecting group conversations
        $groupConversations = $this->conversationRepository->getGroupConversations($this->currentUser);

        // creating form
        $form  = $this->formFactory->create(CreateGroupConversationType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            dd($form->getData());
        }

        return $this->render('chat_groups/index.html.twig', [
            'conversations' => $groupConversations,
            'form'          => $form->createView(),
        ]);
    }

    #[Route('/chat/groups/create', name: "app_chat_groups_form")]
    public function chatGroupForm(Request $request): Response
    {
        $form = $this->formFactory->create(CreateGroupConversationType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            dd($form->getData());
        }

        return $this->render('chat_groups/_createGroupConversationForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

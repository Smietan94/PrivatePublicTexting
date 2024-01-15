<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    private User $currentUser;

    public function __construct(
        private Security       $security,
        private UserRepository $userRepository
    ) {
        // collecting current user
        $username          = $this->security->getUser()->getUserIdentifier();
        $this->currentUser = $this->userRepository->findOneBy(['username' => $username]);
    }

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
}
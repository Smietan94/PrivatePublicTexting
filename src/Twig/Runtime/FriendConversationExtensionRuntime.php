<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

class FriendConversationExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Security               $security,
        private UserRepository         $userRepository,
        private ConversationRepository $conversationRepository
    ) {
        // Inject dependencies if needed
    }

    public function getConversationId(User $friend): int
    {
        $username     = $this->security->getUser()->getUserIdentifier();
        $currentUser  = $this->userRepository->findOneBy(['username' => $username]);
        $conversation = $this->conversationRepository->getFriendConversation($currentUser, $friend);

        return $conversation->getId();
    }
}

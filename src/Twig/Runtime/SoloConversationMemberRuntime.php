<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

class SoloConversationMemberRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository
    ) {
    }

    public function getReceiver(Conversation $conversation): User
    {
        $username    = $this->security->getUser()->getUserIdentifier();
        $currentUser = $this->userRepository->findOneBy(['username' => $username]);
        $members     = $conversation->getConversationMembers()->toArray();
        $member      = array_values(array_filter($members, function ($member) use ($currentUser) {
            if ($member !== $currentUser) {
                return $member;
            }
        }));

        return $member[0];
    }
}

<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

class ConversationMemberRuntime implements RuntimeExtensionInterface
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

    public function getReceiver(Conversation $conversation): User
    {
        $members = $conversation->getConversationMembers()->toArray();
        $member  = array_values(array_filter($members, function ($member) {
            if ($member !== $this->currentUser) {
                return $member;
            }
        }));

        return $member[0];
    }

    public function getReceiversIds(Conversation $conversation): array
    {
        $members = $conversation->getConversationMembers()->toArray();
        return array_values(array_filter(array_map(function ($member) {
            if ($member !== $this->currentUser) {
                return $member->getId();
            }
        }, $members)));
    }

    public function getConversationTopics(Conversation $conversation): array
    {
        $members = $conversation->getConversationMembers()->toArray();
        return array_values(array_filter(array_map(function ($member) {
            if ($member !== $this->currentUser) {
                return sprintf('notifications%d', $member->getId());
            }
        }, $members)));
    }
}

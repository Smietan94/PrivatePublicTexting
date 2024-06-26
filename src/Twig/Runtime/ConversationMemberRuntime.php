<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Entity\Constants\Constant;
use App\Entity\Conversation;
use App\Entity\User;
use App\Enum\UserStatus;
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

    /**
     * get conversation receiver
     *
     * @param  mixed $conversation
     * @return User
     */
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

    /**
     * get conversation receivers ids
     *
     * @param  mixed $conversation
     * @return array
     */
    public function getReceiversIds(Conversation $conversation): array
    {
        $members = $conversation->getConversationMembers()->toArray();
        return array_values(array_filter(array_map(function ($member) {
            if ($member !== $this->currentUser) {
                return $member->getId();
            }
        }, $members)));
    }

    /**
     * get mercure conversation topics
     *
     * @param  mixed $conversation
     * @return array
     */
    public function getConversationTopics(Conversation $conversation): array
    {
        $members = $conversation->getConversationMembers()->toArray();
        return array_values(array_filter(array_map(function ($member) {
            if ($member !== $this->currentUser) {
                return sprintf(Constant::NOTIFICATIONS, $member->getId());
            }
        }, $members)));
    }

    /**
     * check if user status is deleted
     *
     * @param  mixed $conversation
     * @return bool
     */
    public function friendIsDeleted(Conversation $conversation): bool
    {
        $receiver = $this->getReceiver($conversation);

        return $receiver->getStatus() === UserStatus::DELETED->toInt();
    }
}

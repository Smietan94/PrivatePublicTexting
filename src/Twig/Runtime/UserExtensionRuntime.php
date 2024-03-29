<?php

namespace App\Twig\Runtime;

use App\Entity\Constants\Constant;
use App\Entity\User;
use Twig\Extension\RuntimeExtensionInterface;

class UserExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function getFriendsTopics(User $user)
    {
        $friends = $user->getFriends()->toArray();
        return array_map(
            fn ($friend) => sprintf(Constant::NOTIFICATIONS, $friend->getId()),
            $friends
        );
    }
}

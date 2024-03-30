<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Enum\UserStatus;
use App\Repository\UserRepository;
use Twig\Extension\RuntimeExtensionInterface;

class GetUsernameExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * get username by user id
     *
     * @param  mixed $value
     * @return string
     */
    public function userName(int $value): string
    {
        $user = $this->userRepository->find($value);

        if ($user->getStatus() === UserStatus::DELETED->toInt()) {
            return 'Chat User';
        } else {
            return  $user->getUsername();
        }
    }
}

<?php

namespace App\Twig\Runtime;

use App\Repository\UserRepository;
use Twig\Extension\RuntimeExtensionInterface;

class GetUsernameExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function userName($value)
    {
        return $this->userRepository->find((int) $value)->getUsername();
    }
}

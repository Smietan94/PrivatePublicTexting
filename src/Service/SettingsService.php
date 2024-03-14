<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class SettingsService
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private UserRepository       $userRepository
    ) {
    }

    /**
     * createChangeEmailForm
     *
     * @return FormInterface
     */
    public function createSettingsForm(string $formType, string $action): FormInterface
    {
        return $this->formFactory->create($formType, null, ['action' => $action]);
    }

    /**
     * updateEmail
     *
     * @param  User  $currentUser
     * @param  array $data
     * @return User
     */
    public function updateEmail(User $currentUser, array $data): User
    {
        $email       = $data['new_email'];
        $currentUser = $currentUser->setEmail($email);
        $this->userRepository->saveUpdates($currentUser);

        return $currentUser;
    }

    /**
     * updateUsername
     *
     * @param  User  $currentUser
     * @param  array $data
     * @return User
     */
    public function updateUsername(User $currentUser, array $data): User
    {
        $username    = $data['new_username'];
        $currentUser = $currentUser->setUsername($username);
        $this->userRepository->saveUpdates($currentUser);

        return $currentUser;
    }

    public function printData(array $data) {
        dd($data);
    }
}
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\User;
use App\Enum\UserStatus;
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
     * @param  string $formType
     * @param  string $action
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

    /**
     * updatePassword
     *
     * @param  User  $currentUser
     * @param  array $data
     * @return User
     */
    public function updatePassword(User $currentUser, array $data): User
    {
        $this->userRepository->upgradePassword(
            $currentUser,
            $data['new_password']
        );
        $this->userRepository->saveUpdates($currentUser);

        return $currentUser;
    }

    public function processUserSoftDelete(User $currentUser, array $data): User
    {
        // $nameHelper  = (new \DateTime())->format('dmYHisu');
        // $deletedName = sprintf(Constant::DELETED_USER_NAME_FORMAT, $nameHelper);

        // $currentUser->setStatus(UserStatus::DELETED->toInt());
        // $currentUser->setUsername($deletedName);
        // $currentUser->setName($deletedName);
        // $currentUser->setEmail(sprintf(
        //     Constant::DELETED_USER_EMAIL_FORMAT,
        //     $currentUser->getEmail(),

        //     $nameHelper
        // ));

        // przemyśleć jak to zaimplementować
        return $currentUser;
    }
}
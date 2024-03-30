<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Constants\Constant;
use App\Entity\FriendHistory;
use App\Entity\User;
use App\Enum\ConversationType;
use App\Enum\FriendStatus;
use App\Enum\UserStatus;
use App\Repository\FriendHistoryRepository;
use App\Repository\UserRepository;
use App\Tests\Unit\Entity\ConversationTest;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class SettingsService
{
    public function __construct(
        private FormFactoryInterface    $formFactory,
        private UserRepository          $userRepository,
        private FriendHistoryRepository $friendHistoryRepository
    ) {
    }

    /**
     * create change email form
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
     * update email
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
     * update username
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
     * update password
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

    /**
     * process user soft delete
     *
     * @param  User  $currentUser
     * @param  array $data
     * @return User
     */
    public function processUserSoftDelete(User $currentUser, array $data): User
    {
        $nameHelper  = (new \DateTime())->format('dmYHisu');
        $deletedName = sprintf(Constant::DELETED_USER_NAME_FORMAT, $nameHelper);

        $currentUser->setStatus(UserStatus::DELETED->toInt());
        $currentUser->setUsername($deletedName);
        $currentUser->setName($deletedName);
        $currentUser->setEmail(sprintf(
            Constant::DELETED_USER_EMAIL_FORMAT,
            $currentUser->getEmail(),
            $nameHelper
        ));

        foreach ($currentUser->getFriends() as $friend) {
            $currentUser->removeFriend($friend);
            $friendHistory = $this->friendHistoryRepository->getFriendHistory($currentUser, $friend);
            $friendHistory->setStatus(FriendStatus::DELETED->toInt());
        }

        foreach ($currentUser->getConversations() as $conversation) {
            if ($conversation->getConversationType() === ConversationType::GROUP->toInt()) {
                $currentUser->removeConversation($conversation);
            }
        }

        $this->userRepository->saveUpdates($currentUser);

        $session = new Session();
        $session->invalidate();

        return $currentUser;
    }
}
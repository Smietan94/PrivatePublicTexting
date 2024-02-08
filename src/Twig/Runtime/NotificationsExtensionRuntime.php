<?php

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Repository\NotificationRepository;
use Twig\Extension\RuntimeExtensionInterface;

class NotificationsExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
        // Inject dependencies if needed
    }

    public function getUnseenReceivedNotifications(User $user)
    {
        return $this->notificationRepository->getUnseenNotifications($user);
    }
}

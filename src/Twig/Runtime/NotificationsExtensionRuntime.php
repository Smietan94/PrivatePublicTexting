<?php

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Twig\Extension\RuntimeExtensionInterface;

class NotificationsExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private NotificationRepository $notificationRepository
    ) {
        // Inject dependencies if needed
    }

    /**
     * get unseen received notifications
     *
     * @param  User $user
     * @return Notification[]
     */
    public function getUnseenReceivedNotifications(User $user): array
    {
        return $this->notificationRepository->getUnseenNotifications($user);
    }

    /**
     * get notification type string
     *
     * @param  int $type
     * @return string
     */
    public function getNotificationTypeString(int $type): string
    {
        return NotificationType::tryFrom($type)->toString();
    }
}

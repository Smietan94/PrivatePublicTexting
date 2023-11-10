<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\UserSatatus;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout'
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()->getUser();
        $this->userRepository->changeStatus(UserSatatus::LOGGEDOUT->toInt(), $user);
        $this->userRepository->updateLastSeen($user);
    }
}
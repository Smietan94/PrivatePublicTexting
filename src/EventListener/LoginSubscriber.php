<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\UserStatus;
use App\Repository\UserRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository
    ) {
    }

    /**
     * getSubscribedEvents
     *
     * @return LoginSuccessEvent[] array
     */
    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onSuccessLogin'];
    }

    /**
     * onSuccessLogin
     *
     * @param  LoginSuccessEvent $event
     * @return void
     */
    public function onSuccessLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getPassport()->getUser();
        $this->userRepository->changeStatus(UserStatus::ACTIVE->toInt(), $user);
    }
}
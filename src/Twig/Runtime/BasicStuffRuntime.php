<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Entity\Constants\Constant;
use App\Entity\Constants\RouteName;
use App\Entity\Notification;
use App\Enum\NotificationType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class BasicStuffRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
        // Inject dependencies if needed
    }

    /**
     * pushes arguments to array
     *
     * @param  mixed $array
     * @param  mixed $argument
     * @return array
     */
    public function push(array $array, mixed $argument): array
    {
        if (is_array($argument)) {
            foreach($argument as $arg) {
                array_push($array, $arg);
            }
        } else {
            array_push($array, $argument);
        }

        return $array;
    }

    /**
     * get route name from RouteName
     *
     * @param  string $routeName // case insensitive
     * @return string
     */
    public function getRouteName(string $routeName): string
    {
        $reflection = new \ReflectionClass(RouteName::class);

        return $reflection->getConstant(strtoupper($routeName));
    }

    /**
     * get constant from Constant
     *
     * @param  string $constName
     * @return mixed
     */
    public function getConstant(string $constName): mixed
    {
        $reflection = new \ReflectionClass(Constant::class);

        return $reflection->getConstant(strtoupper($constName));
    }

    /**
     * get Href from notification
     *
     * @param  Notification $notification
     * @return ?string
     */
    public function getHref(Notification $notification): ?string
    {
        $href           = NotificationType::tryFrom($notification->getNotificationType())->getRouteName();
        $conversationId = $notification->getConversationId();
        
        return match (true) {
            $href === '#'     => $href,
            !!$conversationId => $this->urlGenerator->generate($href, ['conversationId' => $conversationId]),
            default           => $this->urlGenerator->generate($href)
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\FriendConversationExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FriendConversationExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('conversationId', [FriendConversationExtensionRuntime::class, 'getConversationId'])
        ];
    }
}

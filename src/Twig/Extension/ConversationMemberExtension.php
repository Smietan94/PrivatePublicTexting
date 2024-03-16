<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\ConversationMemberRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ConversationMemberExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('getReceiver', [ConversationMemberRuntime::class, 'getReceiver']),
            new TwigFilter('getReceiversIds', [ConversationMemberRuntime::class, 'getReceiversIds']),
            new TwigFilter('getConversationTopics', [ConversationMemberRuntime::class, 'getConversationTopics'])
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('friendIsDeleted', [ConversationMemberRuntime::class, 'friendIsDeleted'])
        ];
    }
}

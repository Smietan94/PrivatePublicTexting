<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\ConversationMemberRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ConversationMemberExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('GetReceiver', [ConversationMemberRuntime::class, 'getReceiver']),
            new TwigFilter('GetReceiversIds', [ConversationMemberRuntime::class, 'getReceiversIds']),
            new TwigFilter('GetConversationTopics', [ConversationMemberRuntime::class, 'getConversationTopics'])
        ];
    }
}

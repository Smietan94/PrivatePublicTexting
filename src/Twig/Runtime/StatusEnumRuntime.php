<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Enum\FriendStatus;
use Twig\Extension\RuntimeExtensionInterface;

class StatusEnumRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    /**
     * gets friend status
     *
     * @param  mixed $value
     * @return void
     */
    public function formatEnum($value)
    {
        return FriendStatus::tryFrom((int) $value)->toString();
    }
}

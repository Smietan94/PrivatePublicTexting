<?php

namespace App\Twig\Runtime;

use App\Enum\FriendRequestStatus;
use Twig\Extension\RuntimeExtensionInterface;

class StatusEnumRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function formatEnum($value)
    {
        return FriendRequestStatus::tryFrom((int) $value)->toString();
    }
}

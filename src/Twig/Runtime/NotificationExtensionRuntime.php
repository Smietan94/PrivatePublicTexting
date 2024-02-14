<?php

namespace App\Twig\Runtime;

use App\Entity\Notification;
use Twig\Extension\RuntimeExtensionInterface;

class NotificationExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function processNotification(Notification $notification)
    {
        // ...
    }
}

`<a href="#" class="list-group-item list-group-item-action" aria-current="true">
<div class="d-flex w-100 justify-content-between">
  <h5 class="mb-1">List group item heading</h5>
  <small>3 days ago</small>
</div>
<p class="mb-1">Some placeholder content in a paragraph.</p>
<small>And some small print.</small>
</a>`;
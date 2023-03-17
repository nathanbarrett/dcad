<?php

declare(strict_types=1);

namespace App\Contracts;

enum NotificationSubscriptionType: string
{
    case OWNERSHIP_CHANGES = 'ownership_changes';
}

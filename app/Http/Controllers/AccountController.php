<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateNotificationSubscriptionRequest;
use App\Models\NotificationSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function createNotificationSubscription(CreateNotificationSubscriptionRequest $request): JsonResponse
    {
        $params = $request->only(['name', 'type']);
        $params['filters'] = $request->getFilters();

        /** @var NotificationSubscription $subscription */
        $subscription = $request->user()->notification_subscriptions()->create($params);

        return response()->json(compact('subscription'));
    }
}

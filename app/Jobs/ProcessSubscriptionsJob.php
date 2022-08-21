<?php

namespace App\Jobs;

use App\Models\NotificationSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $subscriptions = NotificationSubscription::query()
            ->where('active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->type === NotificationSubscription::TYPE_OWNERSHIP_CHANGES) {
                $this->processOwnershipChanges($subscription);
                continue;
            }
        }
    }

    private function processOwnershipChangesSubscription(NotificationSubscription $subscription): void
    {

    }
}

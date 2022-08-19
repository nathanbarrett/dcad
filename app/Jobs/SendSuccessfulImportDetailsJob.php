<?php

namespace App\Jobs;

use App\Models\Property;
use App\Notifications\DcadImportSuccessfulNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendSuccessfulImportDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Notification::route('slack', config('services.slack.webhooks.dcad'))
            ->notify(new DcadImportSuccessfulNotification(
                $this->getTotalPropertiesCreated(),
                $this->getTotalOwnershipsUpdated()
            ));
    }

    private function getTotalPropertiesCreated(): int
    {
        return Property::query()
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
    }

    private function getTotalOwnershipsUpdated(): int
    {
        return DB::table('owner_property')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
    }
}

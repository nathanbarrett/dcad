<?php

namespace App\Jobs;

use App\Models\ImportLog;
use App\Notifications\DcadImportSuccessfulNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendSuccessfulImportDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $importLogId)
    {
        //
    }

    public function handle(): void
    {
        /* @var ImportLog $importLog */
        $importLog = ImportLog::find($this->importLogId);
        Notification::route('slack', config('services.slack.webhooks.dcad'))
            ->notify(new DcadImportSuccessfulNotification(
                $importLog->properties_created,
                $importLog->ownerships_updated
            ));
        $importLog->finished_at = now();
        $importLog->save();
    }
}

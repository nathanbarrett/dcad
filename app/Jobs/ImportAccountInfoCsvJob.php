<?php

namespace App\Jobs;

use App\Services\DcadProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportAccountInfoCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $folderPath)
    {
        //
    }

    public function handle(DcadProcessor $dcad): void
    {
        $start = now();
        $stats = $dcad->importAccountInfoCsv($this->folderPath);
        // TODO log this to CloudWatch
        Log::info("account_info.csv imported", [
            'properties_created' => $stats->propertyCreations,
            'owners_created' => $stats->ownerCreations,
            'existing_relation_no_update' => $stats->noUpdatesRows,
            'processing_minutes' => now()->diffInMinutes($start),
        ]);
    }
}

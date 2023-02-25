<?php

namespace App\Jobs;

use App\Models\ImportLog;
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

    public int $timeout = 172800;

    public function __construct(public readonly string $folderPath, public readonly int $importLogId)
    {
        //
    }

    public function handle(DcadProcessor $dcad): void
    {
        $start = now();
        $stats = $dcad->importAccountInfoCsv($this->folderPath);

        ImportLog::query()->where('id', $this->importLogId)
            ->update([
                'properties_created' => $stats->propertyCreations,
                'ownerships_updated' => $stats->ownerCreations,
            ]);

        Log::info("account_info.csv imported", array_merge($stats->toArray(), ['duration' => now()->diffForHumans($start)]));
    }
}

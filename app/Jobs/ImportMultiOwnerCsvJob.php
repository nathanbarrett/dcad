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

class ImportMultiOwnerCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public readonly string $folderPath)
    {}

    public function handle(DcadProcessor $dcad): void
    {
        $start = now();
        $stats = $dcad->importMultiOwnerCsv($this->folderPath);
        // TODO log this info to CloudWatch
        Log::info("imported multi_owner.csv", [
            'zero_record_matches' => $stats->zeroRecordMatches,
            'multiple_record_matches' => $stats->multipleRecordMatches,
            'new_record_updates' => $stats->newRecordUpdates,
            'processing_minutes' => now()->diffInMinutes($start),
        ]);
    }
}

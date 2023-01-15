<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CleanUpAllManualUploads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        $disk = Storage::disk('s3-dcad-data');

        $manuallyUploadedFiles = collect($disk->allFiles('dcad_uploads'))
            ->filter(fn (string $key) => Str::of($key)->lower()->test('^dcad_uploads\/dcad\S+\.zip$'));

        if ($manuallyUploadedFiles->isEmpty()) {
            return;
        }

        $disk->delete($manuallyUploadedFiles->toArray());

        Log::debug('Cleaned up manually uploaded files', [
            'files' => $manuallyUploadedFiles->toArray(),
        ]);
    }
}

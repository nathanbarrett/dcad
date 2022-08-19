<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CleanUpAndStoreDcadDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly bool $skipArchiveStorage = false) {}

    public function handle(): void
    {
        $foldersDeleted = $this->deleteAllUnzippedDcadData();

        $zippedFilesStored = $this->storeAllZippedDcadData();

        $this->localDisk()->delete($zippedFilesStored);

        $remoteArchivesDeleted = $this->deleteOldRemoteArchives();

        Log::info(
            'Dcad Info Cleanup Complete',
            compact('foldersDeleted', 'zippedFilesStored', 'remoteArchivesDeleted')
        );
    }

    private function deleteAllUnzippedDcadData(): array
    {
        $disk = Storage::disk('local');
        $folders = $disk->allDirectories('dcad');
        foreach ($folders as $folder) {
            $disk->deleteDirectory($folder);
        }

        return $folders;
    }

    private function storeAllZippedDcadData(): array
    {
        $files = collect($this->localDisk()->allFiles('dcad'))
            ->filter(fn ($file) => Str::endsWith($file, ".zip"));

        if ($this->skipArchiveStorage) {
            return $files->toArray();
        }

        $files->each(function (string $filePath) {
             $this->remoteDisk()->putFileAs(
                 $this->remoteStoragePath(),
                 new File(storage_path('app/' . $filePath)),
                 now()->format('Y-m-d') . '_dcad_data.zip'
             );
        });

        return $files->toArray();
    }

    private function remoteStoragePath(): string
    {
        return 'dcad_downloads/' . app()->environment();
    }

    private function localDisk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }

    private function remoteDisk(): FilesystemAdapter
    {
        return Storage::disk('s3-dcad-data');
    }

    private function deleteOldRemoteArchives(): array
    {
        $files = $this->remoteDisk()->allFiles($this->remoteStoragePath());
        $cutoffDate = now()->subDays(config()->get('dcad.archive_retention_days'));
        $deleteFiles = [];

        foreach ($files as $file) {
            if (! Str::endsWith($file, '.zip')) {
                continue;
            }
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $file, $matches)) {
                $date = $matches[0];
                if (Carbon::createFromFormat('Y-m-d', $date)->lessThan($cutoffDate)) {
                    $deleteFiles[] = $file;
                }
            }
        }

        if (count($deleteFiles) > 0) {
            $this->remoteDisk()->delete($deleteFiles);
        }

        return $deleteFiles;
    }
}

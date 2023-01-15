<?php

namespace App\Console\Commands;

use App\Jobs\AccountInfoImportCleanup;
use App\Jobs\CleanUpAllManualUploads;
use App\Jobs\CleanUpAndStoreDcadDataJob;
use App\Jobs\ImportAccountInfoCsvJob;
use App\Jobs\ImportMultiOwnerCsvJob;
use App\Jobs\SendSuccessfulImportDetailsJob;
use App\Models\ImportLog;
use App\Notifications\DcadImportErroredNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class DownloadAndImportDcadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcad:cron:download-import-data
                            {--skip-account-info : skips processing account_info.csv and does not store the archive file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads DCAD data and updates the database';

    private ?ProgressBar $downloadProgress = null;

    public function handle(): int
    {
        Log::debug('DCAD Import Started');
        $importLog = ImportLog::create([
            'started_at' => now(),
        ]);
        $start = now();
        if (! $data = $this->downloadFromS3()) {
            return self::FAILURE;
        }
        $savedFilePath = $this->store($data);
        $this->info("Downloaded file in " . now()->diffInSeconds($start) . " seconds");

        $start = now();
        if (! $folderPath = $this->unzipFile($savedFilePath)) {
            return self::FAILURE;
        }
        $this->info("Unzipped file in " . now()->diffInSeconds($start) . " seconds");

        $jobChain = [];
        if (!$this->option('skip-account-info')) {
            $jobChain[] = new ImportAccountInfoCsvJob(
                folderPath: $folderPath,
                importLogId: $importLog->id
            );
            $jobChain[] = new AccountInfoImportCleanup();
        }
        $jobChain[] = new ImportMultiOwnerCsvJob(folderPath: $folderPath);
        $jobChain[] = new CleanUpAndStoreDcadDataJob(
            skipArchiveStorage: (bool) $this->option('skip-account-info')
        );

        if (!$this->option('skip-account-info')) {
            $jobChain[] = new CleanUpAllManualUploads();
            $jobChain[] = new SendSuccessfulImportDetailsJob(
                importLogId: $importLog->id
            );
        }
        $importLogId = $importLog->id;
        Bus::chain($jobChain)
            ->catch(function (Throwable $e) use ($importLogId) {
                dispatch(new CleanUpAndStoreDcadDataJob(true));
                Log::critical('DCAD Import Failed', [
                    'importLogId' => $importLogId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                Notification::route('slack', config('services.slack.webhooks.dcad'))
                    ->notify(new DcadImportErroredNotification($e->getMessage(), $e->getCode()));
                ImportLog::query()->where('id', $importLogId)
                    ->update([
                        'errored_at' => now(),
                        'error_message' => $e->getMessage(),
                    ]);
            })
            ->dispatch();

        return self::SUCCESS;
    }

    /**
     * Checks for any new files in the dcad_uploads folder and downloads
     * them into the storage/app/dcad folder
     * @return string|null
     */
    private function downloadFromS3(): ?string
    {
        $s3 = Storage::disk('s3-dcad-data');
        $files = $s3->allFiles('dcad_uploads');
        if (count($files) === 0) {
            $this->tsError('No files found in dcad_uploads folder');
            return null;
        }
        $key = $files[0];
        if (! Str::of($key)->lower()->test('/^dcad_uploads\/dcad\S+\.zip$/')) {
            $this->tsError('Not a valid DCAD file');
            return null;
        }
        $this->tsInfo("Downloading file from S3: $key");

        $data = $s3->get($key);
        $this->tsInfo("Downloaded file from S3: $key");

        return $data;
    }

    /**
     * Monitors download progress and updates the download progress bar
     * @param mixed $resource
     * @param float $download_size
     * @param float $downloaded
     * @param float $upload_size
     * @param float $uploaded
     * @return void
     */
    private function outputDownloadProgress($resource, $download_size, $downloaded, $upload_size, $uploaded)
    {
        // $this->info('download progress run: ' . $download_size);
        if(! $download_size || $download_size < 0) {
            return;
        }
        if( ! $this->downloadProgress) {
            $this->downloadProgress = $this->output->createProgressBar();
            $this->downloadProgress->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $this->downloadProgress->start($download_size);
        }

        $complete = round($downloaded / $download_size * 100);
        if($complete >= 100) {
            $this->downloadProgress->finish();
            return;
        }

        $this->downloadProgress->setProgress($downloaded);
    }

    /**
     * Stores the downloaded file to the appropriate location
     *
     * @param mixed $data
     * @return string - file path
     */
    private function store($data): string
    {
        File::ensureDirectoryExists(storage_path('app/dcad'));
        $filePath = storage_path('app/dcad/dcad_data_' . now()->format('Y-m-d'));
        $savedFile = $filePath . '.zip';
        $this->info("\nSaving file to " . $savedFile);
        $file = fopen($savedFile, "w+");
        fputs($file, $data);
        fclose($file);

        return $savedFile;
    }

    private function unzipFile(string $savedFile): ?string
    {
        $lastDot = strrpos($savedFile, '.');
        $filePath = substr($savedFile, 0, $lastDot);
        $this->info('Unzipping file...');
        try {
            exec("unzip $savedFile -d $filePath");
        } catch(\Exception $exception) {
            $this->error("Exception unzipping file after download: " . $exception->getMessage());
            return null;
        }

        return $filePath;
    }
}

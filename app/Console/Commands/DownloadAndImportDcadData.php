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
use App\Services\DcadDownloader;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class DownloadAndImportDcadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcad:cron:download-import-data
                            {--skip-account-info : skips processing account_info.csv and does not store the archive file}
                            {--download-only : only downloads the file and does not process it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads DCAD data and updates the database';

    public function handle(DcadDownloader $downloader): int
    {
        Log::debug('DCAD Import Started');
        $importLog = ImportLog::create([
            'started_at' => now(),
        ]);
        $this->tsInfo("Starting DCAD Download");
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat('debug');
        $folderPath = $downloader
            ->withDownloadProgress($progressBar)
            ->downloadAndUnzip();
        $this->tsInfo("Downloaded and extracted file to $folderPath");

        if ($this->option('download-only')) {
            $this->tsInfo("Download only option set, exiting");
            $importLog->delete();
            return self::SUCCESS;
        }

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
                ImportLog::query()
                    ->where('id', $importLogId)
                    ->update([
                        'errored_at' => now(),
                        'error_message' => $e->getMessage(),
                    ]);
            })
            ->dispatch();

        $this->tsInfo("DCAD Import Started");
        return self::SUCCESS;
    }
}

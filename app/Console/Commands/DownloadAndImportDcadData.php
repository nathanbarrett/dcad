<?php

namespace App\Console\Commands;

use App\Jobs\CleanUpAndStoreDcadDataJob;
use App\Jobs\ImportAccountInfoCsvJob;
use App\Jobs\ImportMultiOwnerCsvJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class DownloadAndImportDcadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcad:cron:download-import-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads DCAD data and updates the database';

    private ?ProgressBar $downloadProgress = null;

    public function handle(): int
    {
        $start = now();

        if (! $data = $this->download()) {
            return self::FAILURE;
        }
        $savedFilePath = $this->store($data);
        $this->info("Downloaded file in " . now()->diffInSeconds($start) . " seconds");

        $start = now();
        if (! $folderPath = $this->unzipFile($savedFilePath)) {
            return self::FAILURE;
        }
        $this->info("Unzipped file in " . now()->diffInSeconds($start) . " seconds");

        Bus::chain([
            new ImportAccountInfoCsvJob($folderPath),
            new ImportMultiOwnerCsvJob($folderPath),
            new CleanUpAndStoreDcadDataJob,
        ])->catch(function (Throwable $e) {
            dispatch(new CleanUpAndStoreDcadDataJob);
        })->dispatch();

        return self::SUCCESS;
    }

    private function download(): string|bool
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $ch = curl_init();
        $source = config('dcad.download_url');
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'outputDownloadProgress'));
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $this->info("Downloading DCAD info...");

        try {
            $data = curl_exec($ch);
        } catch(\Exception $exception) {
            $this->error("Exception while trying to download file: " . $exception->getMessage());
            if ($this->downloadProgress) {
                $this->downloadProgress->finish();
            }
            curl_close($ch);
            return false;
        }
        curl_close($ch);

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

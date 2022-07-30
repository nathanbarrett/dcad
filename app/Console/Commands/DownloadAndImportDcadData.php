<?php

namespace App\Console\Commands;

use App\Services\DcadProcessor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;

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

    public function handle(DcadProcessor $dcad): int
    {
        $start = now();

//        if (! $data = $this->download()) {
//            return self::FAILURE;
//        }
//
//        $savedFilePath = $this->store($data);
//
//        if (! $folderPath = $this->unzipFile($savedFilePath)) {
//            return self::FAILURE;
//        }

        $folderPath = storage_path('app/dcad/dcad_data_2022-07-29');

//        $this->info("Importing account_info.csv ...");
//        $dcad->importAccountInfoCsv($folderPath, $this->output);

        $this->info("Importing multi_owner.csv ...");
        $dcad->importMultiOwnerCsv($folderPath);

        $this->info("Completed in " . now()->diffInMinutes($start) . " minutes");
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

    private function transferZippedFileToRemoteStorage(string $filePath): void
    {
        $diskName = 's3-dcad-data';
        $fileName = substr($filePath, strrpos($filePath, '/') + 1);
        $savePath = 'dcad_downloads/' . app()->environment() . '/' . $fileName;
        $bucketName = config()->get('filesystems.disks.' . $diskName . '.bucket');
        $region = config()->get('filesystems.disks.' . $diskName . '.region');

        $this->info("Saving to https://" . $bucketName . '.s3.' . $region . '.amazonaws.com/' . $savePath);
        Storage::disk($diskName)->put(
            app()->environment() . '/' . $fileName,
            $filePath
        );
    }
}

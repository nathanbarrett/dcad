<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class DcadDownloader
{
    private ?ProgressBar $downloadProgress = null;

    public function withDownloadProgress(ProgressBar $progressBar): self
    {
        $this->downloadProgress = $progressBar;
        return $this;
    }

    public function downloadAndUnzip(): ?string
    {
        $data = $this->downloadFromDcadSite();
        if (! $data) {
            return null;
        }

        $savedFile = $this->store($data);

        return $this->unzipFile($savedFile);
    }

    private function downloadFromDcadSite(): string|bool
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $ch = curl_init();
        $source = config('dcad.download_url');
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        if ($this->downloadProgress) {
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'outputDownloadProgress'));
        }
        curl_setopt($ch, CURLOPT_NOPROGRESS, !$this->downloadProgress);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        try {
            $data = curl_exec($ch);
        } catch(\Exception $exception) {
            Log::error("Exception while trying to download file: " . $exception->getMessage());
        } finally {
            if ($this->downloadProgress) {
                $this->downloadProgress->finish();
            }
            curl_close($ch);
        }


        return $data ?? false;
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
        $file = fopen($savedFile, "w+");
        fputs($file, $data);
        fclose($file);

        return $savedFile;
    }

    /**
     * Checks for any new files in the dcad_uploads folder and downloads
     * them into the storage/app/dcad folder
     * Use this as a backup in case the DCAD site goes down again 🤪
     * @return string|null
     */
    private function downloadFromS3(): ?string
    {
        $s3 = Storage::disk('s3-dcad-data');
        $files = $s3->allFiles('dcad_uploads');
        if (count($files) === 0) {
            return null;
        }
        $key = $files[0];
        if (! Str::of($key)->lower()->test('/^dcad_uploads\/dcad\S+\.zip$/')) {
            return null;
        }

        $data = $s3->get($key);

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
    private function outputDownloadProgress($resource, $download_size, $downloaded, $upload_size, $uploaded): void
    {
        if(! $this->downloadProgress || ! $download_size || $download_size < 0) {
            return;
        }
        if($this->downloadProgress->getMaxSteps() !== (int)$download_size) {
            $this->downloadProgress->start((int)$download_size);
        }

        $complete = round($downloaded / $download_size * 100);
        if($complete >= 100) {
            $this->downloadProgress->finish();
            return;
        }

        $this->downloadProgress->setProgress((int)$downloaded);
    }

    private function unzipFile(string $savedFile): ?string
    {
        $lastDot = strrpos($savedFile, '.');
        $filePath = substr($savedFile, 0, $lastDot);
        try {
            exec("unzip $savedFile -d $filePath");
        } catch(\Exception $exception) {
            return null;
        }

        return $filePath;
    }

    /**
     * Renames all files in the given directory to make everything lowercase
     * as well as removing all spaces and replacing with underscores
     */
    private function normalizeFileNames(string $folderPath): void
    {
        $files = File::allFiles($folderPath);
        foreach ($files as $file) {
            $newName = (string)Str::of($file->getFilename())
                ->lower()
                ->replaceMatches('/\s+/', '_');
            $newPath = $file->getPath() . '/' . $newName;
            rename($file->getPathname(), $newPath);
        }
    }
}

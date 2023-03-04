<?php namespace App\Services;

use App\Dtos\AccountInfoCsvImportStats;
use App\Dtos\MultiOwnerCsvImportStats;
use App\Imports\AccountInfoCsvImport;
use App\Imports\MultiOwnerCsvImport;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DcadProcessor
{
    /**
     * @throws FileNotFoundException
     */
    public function importAccountInfoCsv(string $path, ?OutputStyle $output = null): AccountInfoCsvImportStats
    {
        $import = new AccountInfoCsvImport();
        if ($output) {
            $import->withOutput($output);
        }

        if (! $accountInfoCsvPath = $this->resolveCsvPath($path, 'account_info.csv')) {
            throw new FileNotFoundException('account_info.csv not found in ' . $path, 404);
        }

        $import->import($accountInfoCsvPath);

        return new AccountInfoCsvImportStats(
            $import->noUpdatesRows ?: 0,
            $import->propertyCreations ?: 0,
            $import->ownerCreations ?: 0,
            $import->nonResidentialProperties ?: 0,
            $import->processedRows ?: 0,
        );
    }

    /**
     * @throws FileNotFoundException
     */
    public function importMultiOwnerCsv(string $path, ?OutputStyle $output = null): MultiOwnerCsvImportStats
    {
        $import = new MultiOwnerCsvImport();
        if ($output) {
            $import->withOutput($output);
        }

        if (! $multiOwnerCsvPath = $this->resolveCsvPath($path, 'multi_owner.csv')) {
            throw new FileNotFoundException('multi_owner.csv not found in ' . $path, 404);
        }

        $import->import($multiOwnerCsvPath);

        return new MultiOwnerCsvImportStats(
            $import->zeroRecordMatches ?: 0,
            $import->multipleRecordMatches ?: 0,
            $import->newRecordUpdates ?: 0
        );
    }

    private function resolveCsvPath(string $path, string $filename): ?string
    {
        if (file_exists($path . '/' . $filename)) {
            return $path . '/' . $filename;
        }
        if (file_exists($path . '/' . strtoupper($filename))) {
            return $path . '/' . strtoupper($filename);
        }
        return null;
    }
}

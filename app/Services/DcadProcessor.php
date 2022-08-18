<?php namespace App\Services;

use App\Dtos\AccountInfoCsvImportStats;
use App\Dtos\MultiOwnerCsvImportStats;
use App\Imports\AccountInfoCsvImport;
use App\Imports\MultiOwnerCsvImport;
use Illuminate\Console\OutputStyle;

class DcadProcessor
{
    public function importAccountInfoCsv(string $path, ?OutputStyle $output = null): AccountInfoCsvImportStats
    {
        $import = new AccountInfoCsvImport();
        if ($output) {
            $import->withOutput($output);
        }
        $import->import($path . '/account_info.csv');

        return new AccountInfoCsvImportStats(
            $import->noUpdatesRows ?: 0,
            $import->propertyCreations ?: 0,
            $import->ownerCreations ?: 0
        );
    }

    public function importMultiOwnerCsv(string $path, ?OutputStyle $output = null): MultiOwnerCsvImportStats
    {
        $import = new MultiOwnerCsvImport();
        if ($output) {
            $import->withOutput($output);
        }
        $import->import($path . '/multi_owner.csv');

        return new MultiOwnerCsvImportStats(
            $import->zeroRecordMatches ?: 0,
            $import->multipleRecordMatches ?: 0,
            $import->newRecordUpdates ?: 0
        );
    }
}

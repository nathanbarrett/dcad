<?php namespace App\Services;

use App\Imports\AccountInfoCsvImport;
use App\Imports\MultiOwnerCsvImport;
use Illuminate\Console\OutputStyle;

class DcadProcessor
{
    public function importAccountInfoCsv(string $path, ?OutputStyle $output = null): void
    {
        $import = (new AccountInfoCsvImport);
        if ($output) {
            $import->withOutput($output);
        }
        $import->import($path . '/account_info.csv');
    }

    public function importMultiOwnerCsv(string $path, ?OutputStyle $output = null): void
    {
        $import = (new MultiOwnerCsvImport);
        if ($output) {
            $import->withOutput($output);
        }
        $import->import($path . '/multi_owner.csv');
    }
}

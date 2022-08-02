<?php
declare(strict_types=1);
namespace App\Imports;

use App\Models\PropertyChange;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\DcadDataNormalizer as Normalizer;
use Illuminate\Support\Facades\Log;

class MultiOwnerCsvImport extends BaseCsvImport
{
    public int $zeroRecordMatches = 0;

    public int $multipleRecordMatches = 0;

    public int $newRecordUpdates = 0;

    public function collection(Collection $rows): void
    {
        /* @var Collection $row */
        foreach ($rows as $index => $row) {
            $this->updateOwnerPercentage($row);
        }
    }

    private function updateOwnerPercentage(Collection $row): void
    {
        $ownerName = Normalizer::ucwordsFormat($row, 'owner_name');
        $accountNumber = trim($row->get('account_num', ''));
        $ownershipPercent = Normalizer::parseFloat($row->get('ownership_pct', ''));
        if (! $ownerName || ! $accountNumber || ! $ownershipPercent) {
            return;
        }

        $records = DB::table('owner_property')
            ->select('owner_property.*')
            ->leftJoin('owners', 'owners.id', '=', 'owner_property.owner_id')
            ->where('owner_property.account_num', '=', $accountNumber)
            ->where('owners.name', '=', $ownerName)
            ->get();

        if ($records->count() === 0) {
            $this->zeroRecordMatches++;
            Log::notice(
                'Zero record matches for owner percentage change',
                compact('ownerName', 'accountNumber', 'ownershipPercent')
            );
            return;
        }
        if ($records->count() > 1) {
            $this->multipleRecordMatches++;
            $pivotIds = $records->pluck('id')->toArray();
            Log::notice(
                'Multiple record matches for owner percentage change',
                compact('ownerName', 'accountNumber', 'ownershipPercent', 'pivotIds')
            );
            return;
        }

        $record = $records->first();

        if (((int) $record->ownership_percent) !== ((int) $ownershipPercent)) {
            DB::table('owner_property')
                ->where('id', $record->id)
                ->update(['ownership_percent' => $ownershipPercent]);
            if (Carbon::createFromFormat('Y-m-d H:i:s', $record->created_at)->isToday()) {
                $this->newRecordUpdates++;
                return;
            }
            PropertyChange::create([
                'property_id' => $record->property_id,
                'type' => PropertyChange::TYPE_OWNER_PERCENTAGE_UPDATE,
                'context' => [
                    'pivot_id' => $record->id,
                    'previous_percentage' => (float) $record->ownership_percent,
                    'new_percentage' => $ownershipPercent,
                ]
            ]);
        }
    }
}

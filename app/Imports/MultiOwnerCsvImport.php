<?php
declare(strict_types=1);
namespace App\Imports;

use App\Models\Owner;
use App\Models\PropertyChange;
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
        $ownershipPercent = round(Normalizer::parseFloat($row->get('ownership_pct', '')), 2);
        if (! $ownerName || ! $accountNumber || ! $ownershipPercent) {
            return;
        }

        $propertyOwners = DB::table('owner_property')
            ->select('owner_property.*', 'owners.name')
            ->leftJoin('owners', 'owners.id', '=', 'owner_property.owner_id')
            ->where('owner_property.account_num', '=', $accountNumber)
            ->orderBy('active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($propertyOwners->count() === 0) {
            $this->zeroRecordMatches++;
            return;
        }

        $ownershipExisted = false;
        foreach ($propertyOwners as $ownerProperty) {
            if ($ownerProperty->name !== $ownerName) {
                continue;
            }
            $ownershipExisted = true;
            $recordOwnership = round(Normalizer::parseFloat($ownerProperty->ownership_percent) ?: 0, 2);
            if ($ownershipPercent !== $recordOwnership) {
                DB::table('owner_property')
                    ->where('id', '=', $ownerProperty->id)
                    ->update([
                        'ownership_percent' => $ownershipPercent,
                        'updated_at' => now()
                    ]);
                PropertyChange::create([
                    'property_id' => $ownerProperty->property_id,
                    'type' => PropertyChange::TYPE_OWNER_PERCENTAGE_UPDATE,
                    'context' => [
                        'pivot_id' => $ownerProperty->id,
                        'previous_percentage' => (float) $ownerProperty->ownership_percent,
                        'new_percentage' => $ownershipPercent,
                    ]
                ]);
            }
            break;
        }

        if ($ownershipExisted) {
            return;
        }

        // attach a new minimal owner here since there is no way to
        // match this name with any other identical names
        $coOwnerPivot = $propertyOwners->first();
        $owner = Owner::create([
            'name' => $ownerName,
            'address_1' => 'multiowner',
        ]);
        $owner->properties()->attach([
            $coOwnerPivot->property_id => [
                'active' => $coOwnerPivot->active,
                'deed_transferred_at' => $coOwnerPivot->deed_transferred_at,
                'account_num' => $coOwnerPivot->account_num,
                'ownership_percent' => $ownershipPercent,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        $newPivot = DB::table('owner_property')
            ->select('owner_property.*')
            ->where('owner_id', '=', $owner->id)
            ->first();
        PropertyChange::create([
            'property_id' => $coOwnerPivot->property_id,
            'type' => PropertyChange::TYPE_OWNER_PERCENTAGE_UPDATE,
            'context' => [
                'pivot_id' => $newPivot->id ?? null,
                'previous_percentage' => 0,
                'new_percentage' => $ownershipPercent,
            ]
        ]);
    }
}

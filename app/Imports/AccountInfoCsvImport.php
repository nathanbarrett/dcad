<?php
declare(strict_types=1);
namespace App\Imports;

use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyChange;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use App\Services\DcadDataNormalizer as Normalizer;

class AccountInfoCsvImport extends BaseCsvImport implements WithProgressBar
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row)
        {
            if (! $this->isResidentialProperty($row)) {
                continue;
            }
            /* @var Property $property */
            $property = Property::with('activeOwners')->firstOrCreate([
                'address_1' => $this->getStreetAddress($row),
                'city' => Normalizer::parseCityName($row, "property_city"),
                'state' => 'TX',
                'zip_code' => Normalizer::parseFiveDigitZipCode($row, "property_zipcode"),
            ]);

            $propertyOwner = Owner::firstOrCreate([
                'name' => Normalizer::ucwordsFormat($row, "owner_name1"),
                'name_2' => Normalizer::ucwordsFormat($row, "owner_name2"),
                'address_1' => Normalizer::ucwordsFormat($row, "owner_address_line2"),
                'city' => Normalizer::parseCityName($row, "owner_city"),
                'state' => Normalizer::ucwordsFormat($row, "owner_state"),
                'zip_code' => Normalizer::parseFiveDigitZipCode($row, "owner_zipcode"),
                'country' => Normalizer::parseCountry($row, "owner_country"),
            ]);

            if ($property->activeOwners->contains($propertyOwner)) {
                continue;
            }
            $previousOwners = $property->activeOwners;
            $accountNumber = trim($row->get("account_num", ""));
            if ($previousOwners->count() > 0) {
                DB::table('owner_property')
                    ->where('property_id', $property->id)
                    ->where('active', true)
                    ->when($accountNumber, function (Builder $query, string $accountNumber) {
                        $query->where('account_num', '!=', $accountNumber);
                    })
                    ->update(['active' => false]);
            }
            $property->owners()->attach($propertyOwner->id, [
                'ownership_percent' => 100,
                'active' => true,
                'account_num' => $accountNumber ?: null,
                'deed_transferred_at' =>  Normalizer::parseDate($row, "deed_txfr_date"),
            ]);
            if (! $property->wasRecentlyCreated) {
                PropertyChange::create([
                    'property_id' => $property->id,
                    'type' => PropertyChange::TYPE_OWNER_UPDATE,
                    'context' => [
                        'previous_owner_ids' => $previousOwners->pluck('id')->toArray()
                    ]
                ]);
            }

        }
    }

    private function isResidentialProperty(Collection $row): bool
    {
        return trim(strtolower($row->get('division_cd'))) === "res";
    }

    private function getStreetAddress(Collection $row): ?string
    {
        $streetNum = $row->get("street_num");
        $streetHalfNum = $row->get("street_half_num");
        $streetName = $row->get("full_street_name");
        if( ! $streetNum || ! $streetName) {
            return null;
        }
        return $streetNum . $streetHalfNum . ' ' . Normalizer::forceUcWords($streetName);
    }
}

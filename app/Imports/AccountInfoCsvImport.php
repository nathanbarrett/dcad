<?php
declare(strict_types=1);
namespace App\Imports;

use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyChange;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use App\Services\DcadDataNormalizer as Normalizer;

class AccountInfoCsvImport extends BaseCsvImport implements WithProgressBar
{
    public int $noUpdatesRows = 0;

    public int $propertyCreations = 0;

    public int $ownerCreations = 0;

    public int $processedRows = 0;

    private Carbon $isNewCutoffTime;

    public function __construct()
    {
        $this->isNewCutoffTime = now()->subHours(8);
    }

    public function collection(Collection $rows): void
    {
        /* @var Collection $row */
        foreach ($rows as $index => $row)
        {
            if (! $this->isResidentialProperty($row)) {
                continue;
            }
            /* @var Property $property */
            $property = Property::query()
                ->with('activeOwners')
                ->firstOrCreate([
                    'address_1' => $this->getStreetAddress($row),
                    'city' => Normalizer::parseCityName($row, "property_city"),
                    'state' => 'TX',
                    'zip_code' => Normalizer::parseFiveDigitZipCode($row, "property_zipcode"),
                ]);

            if (!$property->address_1 || !$property->city || !$property->zip_code) {
                Log::debug('Account info row missing critical property data', $row->all());
            }

            if ($property->wasRecentlyCreated) {
                $this->propertyCreations++;
            }

            $propertyOwner = Owner::query()
                ->firstOrCreate([
                    'name' => Normalizer::ucwordsFormat($row, "owner_name1"),
                    'name_2' => Normalizer::ucwordsFormat($row, "owner_name2"),
                    'address_1' => Normalizer::ucwordsFormat($row, "owner_address_line2"),
                    'city' => Normalizer::parseCityName($row, "owner_city"),
                    'state' => Normalizer::ucwordsFormat($row, "owner_state"),
                    'zip_code' => Normalizer::parseFiveDigitZipCode($row, "owner_zipcode"),
                    'country' => Normalizer::parseCountry($row, "owner_country"),
                ]);

            if (
                !(
                    $propertyOwner->name &&
                    $propertyOwner->address_1 &&
                    $propertyOwner->city &&
                    $propertyOwner->state &&
                    $propertyOwner->zip_code &&
                    $propertyOwner->country
                )
            ) {
                Log::debug('Account info row missing critical owner data', $row->all());

            }

            if ($propertyOwner->wasRecentlyCreated) {
                $this->ownerCreations++;
            }

            if ($property->activeOwners->contains($propertyOwner)) {
                $this->noUpdatesRows++;
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
            if ($property->created_at->lessThan($this->isNewCutoffTime)) {
                PropertyChange::create([
                    'property_id' => $property->id,
                    'type' => PropertyChange::TYPE_OWNER_UPDATE,
                    'context' => [
                        'previous_owner_ids' => $previousOwners->pluck('id')->toArray()
                    ]
                ]);
            }
        }
        $this->processedRows += count($rows);
        Log::debug('Processed rows', [
            'processedRows' => $this->processedRows,
            'propertyCreations' => $this->propertyCreations,
            'noUpdateRows' => $this->noUpdatesRows,
            'ownerCreations' => $this->ownerCreations
        ]);
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

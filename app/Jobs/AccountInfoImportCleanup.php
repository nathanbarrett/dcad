<?php

namespace App\Jobs;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AccountInfoImportCleanup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function handle(): void
    {
        Log::debug('Resolving empty zip codes');
        $emptyZipCodesUpdated = $this->resolveEmptyZipCodes();
        Log::debug('Empty zip codes checked', compact('emptyZipCodesUpdated'));
    }

    /**
     * For every property with an empty zip code that has a street address
     * Look for other properties with the same city and street name and give
     * it the same zip code if you can find any
     *
     * @return int
     */
    private function resolveEmptyZipCodes(): int
    {
        $zipsUpdated = 0;
        Property::query()
            ->whereNull('zip_code')
            ->whereNotNull(['address_1', 'city'])
            ->chunkById(100, function (Collection $properties) use (&$zipsUpdated) {

                /* @var Property $property */
                foreach ($properties as $property) {
                    $addressChunks = explode(' ', $property->address_1 ?: '');
                    if (count($addressChunks) < 2) {
                        continue;
                    }
                    array_shift($addressChunks);
                    $streetAddress = implode(' ', $addressChunks);

                    $propertyWithZip = Property::query()
                        ->whereNotNull('zip_code')
                        ->where('city', '=', $property->city)
                        ->where('address_1', 'like', "%" . $streetAddress)
                        ->where('id', '!=', $property->id)
                        ->first();

                    if ($propertyWithZip) {
                        $property->zip_code = $propertyWithZip->zip_code;
                        $property->save();
                        $zipsUpdated++;
                    }
                }
            });

        return $zipsUpdated;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Property;
use Geocodio\Geocodio;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GeocodeAddresses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcad:cron:geocode-addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets lat/long for addresses that don\'t have it';

    /**
     * Gets lat/long for addresses that don't have it within
     * specified zip codes
     *
     * @return int
     */
    public function handle(): int
    {
        $geocoder = new Geocodio();
        $geocoder->setApiKey(config()->get('services.geocodio.api_key'));
        $maxGeocodes = 2500;
        $totalGeocodes = 0;

        DB::table('properties')
            ->selectRaw('properties.id, properties.address_1 as "street", properties.city as "city", properties.state as "state", properties.zip_code as "postal_code"')
            ->whereNull('properties.lat')
            ->whereIn('properties.zip_code', config()->get('zip_codes.preston_hollow'))
            ->chunkById(100, function ($properties) use ($geocoder, $maxGeocodes, &$totalGeocodes) {
                $geocodeData = $properties
                        ->keyBy('id')
                        ->map(function ($property) {
                            return Arr::only((array) $property, ['street', 'city', 'state', 'postal_code']);
                        })
                        ->toArray();

                $response = $geocoder->geocode($geocodeData);

                $results = (array) $response->results;
                foreach ($results as $propertyId => $result) {
                    $matches = $result->response->results;
                    $original = $geocodeData[$propertyId];
                    $matchFound = false;
                    foreach ($matches as $match) {
                        if (
                            $match->accuracy === 1 ||
                            (
                                Str::startsWith($original['street'], $match->address_components->number) &&
                                Str::contains(strtolower($original['street']), strtolower($match->address_components->street))
                            )
                        ) {
                            $matchFound = true;
                            $updateData = [
                                'lat' => $match->location->lat,
                                'lng' => $match->location->lng,
                            ];
                            if ($match->address_components->city !== $original['city']) {
                                Log::debug('City mismatch', [
                                    'original' => $original,
                                    'match' => (array) $match,
                                ]);
                                $updateData['city'] = $match->address_components->city;
                            }
                            if ($match->address_components->zip !== $original['postal_code']) {
                                Log::debug('Zip mismatch', [
                                    'original' => $original,
                                    'match' => (array) $match,
                                ]);
                                $updateData['zip_code'] = $match->address_components->zip;
                            }

                            Property::query()
                                ->where('id', $propertyId)
                                ->update($updateData);
                            break;
                        }
                    }
                    if (!$matchFound) {
                        Property::query()
                            ->where('id', $propertyId)
                            ->update([
                                'lat' => 0,
                                'lng' => 0,
                            ]);
                    }
                }
                $totalGeocodes += $properties->count();
                if ($totalGeocodes >= $maxGeocodes) {
                    return false;
                }
            });


        return self::SUCCESS;
    }
}

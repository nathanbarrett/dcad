<?php namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DcadDataNormalizer
{
    public static function ucwordsFormat(Collection $row, string $key): ?string
    {
        $text = trim($row->get($key, ""));
        return $text ? self::forceUcWords($text) : null;
    }

    public static function parseDate(Collection $row, string $key, bool $canBeFuture = false): ?string
    {
        $date = trim($row->get($key, ""));
        if( ! $date)
        {
            return null;
        }
        /* @var \Carbon\Carbon $c  */
        try {
            $c = Carbon::createFromFormat('m/d/Y', $date);
        } catch(\Exception $exception) {
            Log::warning('Failed to parse date', compact('date', 'key', 'row'));
            return null;
        }
        // attempting to correct some typos from manual data entry
        if( ! $canBeFuture && $c->isFuture()) {
            $yearString = strval($c->year);
            if(Str::endsWith($yearString, '01')) {
                $updatedYearString = substr($yearString, 1, 3) . substr($yearString, 0, 1);
                $c->year(intval($updatedYearString));
            }
            else if(Str::startsWith($yearString, '21')) {
                $yearArray = str_split($yearString);
                $updatedYear = intval($yearArray[0] . $yearArray[2] . $yearArray[1] . $yearArray[3]);
                $c->year($updatedYear);
            }
            if( ! $c->isFuture() && $c->diffInYears(Carbon::now()) <= 100) {
                return $c->format('Y-m-d');
            }
            Log::warning('Failed to correct date', compact('date', 'key', 'row'));
            return null;
        }

        return $c->format('Y-m-d');
    }

    public static function parseCountry(Collection $row, string $key): ?string
    {
        if (! $country = $row->get($key)) {
            return null;
        }
        if(strpos($country, "UNITED STATES OF AMERICA") !== false)
        {
            return "US";
        }
        return self::ucwordsFormat($row, $key);
    }

    public static function parseCityName(Collection $row, string $key): ?string
    {
        $city = preg_replace('/\(DALLAS.*\)/', '', $row->get($key, ""));
        if (! $city) {
            return null;
        }
        return self::forceUcWords($city);
    }

    public static function parseFiveDigitZipCode(Collection $row, string $key): ?string
    {
        if (! $zipcode = $row->get($key)) {
            return null;
        }
        $matches = [];
        if (preg_match('/^\d{5}/', trim($zipcode), $matches)) {
            return $matches[0];
        }
        return null;
    }

    public static function forceUcWords(string $string): string
    {
        $string = trim($string);
        if (! $string) {
            return $string;
        }
        return collect(explode(" ", $string))
            ->filter(fn (string $str) => (bool) trim($str))
            ->map(function (string $str) {
                $str = trim($str);
                if (strlen($str) <= 1) {
                    return strtoupper($str);
                }
                return strtoupper($str[0]) . strtolower(substr($str, 1));
            })
            ->join(" ");
    }

    public static function parseFloat(string $string): ?float
    {
        $string = trim($string);
        if (! $string) {
            return null;
        }
        if (preg_match('/^\d+(\.\d+)?$/', $string)) {
            return (float) $string;
        }
        return null;
    }
}

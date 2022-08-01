<?php

declare(strict_types=1);

namespace App\Dtos;

class MultiOwnerCsvImportStats
{
    public function __construct(
        public readonly int $zeroRecordMatches,
        public readonly int $multipleRecordMatches,
        public readonly int $newRecordUpdates
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Dtos;

class AccountInfoCsvImportStats
{
    public function __construct(
        public readonly int $noUpdatesRows,
        public readonly int $propertyCreations,
        public readonly int $ownerCreations
    )
    {}
}

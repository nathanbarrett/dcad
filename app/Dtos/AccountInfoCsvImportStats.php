<?php

declare(strict_types=1);

namespace App\Dtos;

class AccountInfoCsvImportStats
{
    public function __construct(
        public readonly int $noUpdatesRows,
        public readonly int $propertyCreations,
        public readonly int $ownerCreations,
        public readonly int $nonResidentialProperties,
        public readonly int $processedRows,
    )
    {}

    public function toArray(): array
    {
        return [
            'noUpdatesRows' => $this->noUpdatesRows,
            'propertyCreations' => $this->propertyCreations,
            'ownerCreations' => $this->ownerCreations,
            'nonResidentialProperties' => $this->nonResidentialProperties,
            'processedRows' => $this->processedRows,
        ];
    }
}

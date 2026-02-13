<?php

namespace lameco\craftentryoptimizer\dto;

use craft\elements\Entry;

/**
 * Export Metadata DTO
 * 
 * Immutable data transfer object containing essential entry metadata for import operations.
 * Only includes data actually needed for entry identification and field change detection.
 */
readonly class ExportMetadata
{
    /**
     * @param int $id Entry ID (required for entry lookup)
     * @param int $siteId Site ID (required for entry lookup)
     */
    public function __construct(
        public int $id,
        public int $siteId,
    ) {}

    /**
     * Create metadata from an entry
     * 
     * @param Entry $entry The entry to extract metadata from
     */
    public static function fromEntry(Entry $entry): self
    {
        return new self(
            id: $entry->id,
            siteId: $entry->siteId,
        );
    }

    /**
     * Convert to array for JSON serialization
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'siteId' => $this->siteId,
        ];
    }
}
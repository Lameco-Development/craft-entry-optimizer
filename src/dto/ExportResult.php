<?php

namespace lameco\craftentryoptimizer\dto;

use craft\elements\Entry;

/**
 * Export Result DTO
 * 
 * Immutable data transfer object containing the complete export result.
 * Includes all native entry fields at root level for consistent import.
 */
readonly class ExportResult
{
    /**
     * @param ExportMetadata $metadata Entry metadata
     * @param string $title Entry title (always present, even if empty)
     * @param array $fields Custom field values (field handle => value)
     */
    public function __construct(
        public ExportMetadata $metadata,
        public string $title,
        public array $fields = [],
    ) {}

    /**
     * Create export result from entry
     * 
     * @param Entry $entry The entry to export
     * @param array $fields Custom field values
     */
    public static function fromEntry(Entry $entry, array $fields = []): self
    {
        return new self(
            metadata: ExportMetadata::fromEntry($entry),
            title: $entry->title ?? '',
            fields: $fields,
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
            'metadata' => $this->metadata->toArray(),
            'title' => $this->title,
            ...$this->fields,
        ];
    }
}
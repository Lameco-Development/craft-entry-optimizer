<?php

namespace lameco\craftentryoptimizer\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use lameco\craftentryoptimizer\dto\ExportResult;
use yii\web\NotFoundHttpException;

/**
 * Entry Export Service
 *
 * Orchestrates the export of Craft CMS entries using field handlers.
 * Provides methods to export entries by ID, URL, or Entry object.
 */
class EntryExportService extends Component
{
    /**
     * @var FieldHandlerRegistry Field handler registry
     */
    private FieldHandlerRegistry $handlerRegistry;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Get the registry from the plugin
        $plugin = \lameco\craftentryoptimizer\Plugin::getInstance();
        $this->handlerRegistry = $plugin->fieldHandlerRegistry;
    }

    /**
     * Export entry by ID
     *
     * @param int $entryId The entry ID to export
     * @param int|null $siteId Optional site ID
     * @return ExportResult The export result
     * @throws NotFoundHttpException If entry not found
     */
    public function exportById(int $entryId, ?int $siteId = null): ExportResult
    {
        $query = Entry::find()->id($entryId);

        if ($siteId !== null) {
            $query->siteId($siteId);
        }

        $entry = $query->one();

        if (!$entry) {
            throw new NotFoundHttpException("Entry with ID {$entryId} not found.");
        }

        return $this->exportEntry($entry);
    }

    /**
     * Export entry by slug
     *
     * @param string $slug The entry slug to export
     * @param int|null $siteId Optional site ID
     * @return ExportResult The export result
     * @throws NotFoundHttpException If entry not found
     */
    public function exportBySlug(string $slug, ?int $siteId = null): ExportResult
    {
        $query = Entry::find()->slug($slug);

        if ($siteId !== null) {
            $query->siteId($siteId);
        }

        $entry = $query->one();

        if (!$entry) {
            throw new NotFoundHttpException("Entry with slug '{$slug}' not found.");
        }

        return $this->exportEntry($entry);
    }

    /**
     * Export an entry object
     *
     * @param Entry $entry The entry to export
     * @return ExportResult The export result
     */
    public function exportEntry(Entry $entry): ExportResult
    {
        Craft::debug(
            "Exporting entry ID {$entry->id} ('{$entry->title}')",
            __METHOD__
        );

        // Export all custom fields
        $exportedFields = $this->exportFields($entry);

        // Create and return the result
        $result = ExportResult::fromEntry($entry, $exportedFields);

        Craft::info(
            "Successfully exported entry ID {$entry->id} with " . count($exportedFields) . " fields",
            __METHOD__
        );

        return $result;
    }

    /**
     * Export all custom fields from an entry
     *
     * Uses native Craft serialization first, falls back to custom handlers
     * for complex field types or special transformations.
     *
     * Always includes all fields (even empty ones) to support content optimization workflows.
     *
     * @param Entry $entry The entry to export fields from
     * @return array The exported fields (handle => value)
     */
    private function exportFields(Entry $entry): array
    {
        $exportedFields = [];
        $fieldLayout = $entry->getFieldLayout();

        if (!$fieldLayout) {
            Craft::warning(
                "Entry ID {$entry->id} has no field layout",
                __METHOD__
            );
            return $exportedFields;
        }

        // Get ALL serialized field values from Craft in one call
        // This leverages Craft's native serialization for all field types
        $serializedValues = $entry->getSerializedFieldValues();

        Craft::debug(
            "Native serialization returned " . count($serializedValues) . " fields for entry ID {$entry->id}",
            __METHOD__
        );

        $customFields = $fieldLayout->getCustomFields();

        foreach ($customFields as $field) {
            $handle = $field->handle;

            try {
                // Get the appropriate handler for this field type
                $handler = $this->handlerRegistry->getHandler($field);

                // Check if handler prefers native serialization
                if ($handler->useNativeSerialization() && array_key_exists($handle, $serializedValues)) {
                    // Use Craft's native serialization
                    $exportedValue = $serializedValues[$handle];

                    Craft::debug(
                        "Exported field '{$handle}' using native Craft serialization",
                        __METHOD__
                    );
                } else {
                    // Use custom handler for export (for special transformations)
                    $value = $entry->getFieldValue($handle);
                    $exportedValue = $handler->export($field, $value);

                    Craft::debug(
                        "Exported field '{$handle}' using custom handler: " . get_class($handler),
                        __METHOD__
                    );
                }

                // Include all fields, even empty ones (null, empty string, empty array)
                // Only skip if handler explicitly returns false
                // This ensures content optimization flows can identify all available fields
                if ($exportedValue !== false) {
                    $exportedFields[$handle] = $exportedValue;
                } else {
                    Craft::debug(
                        "Field '{$handle}' explicitly excluded by handler (returned false)",
                        __METHOD__
                    );
                }
            } catch (\Exception $e) {
                // Log the error but continue with other fields
                Craft::warning(
                    "Failed to export field '{$handle}' for entry ID {$entry->id}: " . $e->getMessage(),
                    __METHOD__
                );

                // Optionally include error information in development
                if (YII_DEBUG) {
                    Craft::error(
                        "Field export error details: " . $e->getTraceAsString(),
                        __METHOD__
                    );
                }
            }
        }

        Craft::info(
            "Exported " . count($exportedFields) . " fields for entry ID {$entry->id}",
            __METHOD__
        );

        return $exportedFields;
    }

}

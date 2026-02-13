<?php

namespace lameco\craftentryoptimizer\models;

use Craft;
use craft\base\Model;

/**
 * craft-entry-optimizer settings
 */
class Settings extends Model
{
    /**
     * @var bool Whether to require authentication for export endpoint
     */
    public bool $requireAuthExport = true;

    /**
     * @var bool Whether to require authentication for import endpoint
     */
    public bool $requireAuthImport = true;

    /**
     * @var string|null API key for authentication (if null, uses Craft user authentication)
     */
    public ?string $apiKey = null;

    public function rules(): array
    {
        return [
            [['requireAuthExport', 'requireAuthImport'], 'boolean'],
            [['apiKey'], 'string', 'max' => 255],
        ];
    }
}

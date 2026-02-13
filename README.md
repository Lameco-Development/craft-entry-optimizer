# Craft Entry Optimizer

A Craft CMS 5 plugin for efficient entry data export/import. Export any entry to a clean JSON structure, modify the content (e.g. via AI optimization), and import it back as a draft with only changed fields.

## Features

- **Export entries** to minimal JSON with all field data
- **Import entries** from JSON, creating drafts with only changed fields
- **Extensible field handler system** for custom field type support
- **`__handler` hint system** for reliable field type detection in Matrix blocks
- **Authentication support** with optional API key
- **Change detection** to identify which fields were modified

## Requirements

- Craft CMS 5.8.0 or later
- PHP 8.2 or later

## Installation

```bash
composer require lameco/craft-entry-optimizer
php craft plugin/install _craft-entry-optimizer
```

## Configuration

Configure via **Settings** → **Craft Entry Optimizer**:

- **Require Authentication for Export**: Require user authentication for the export endpoint
- **Require Authentication for Import**: Require user authentication for the import endpoint
- **API Key**: Optional API key for programmatic access without Craft session

## API Endpoints

### Export Entry

```
GET /actions/_craft-entry-optimizer/optimized-entry/export?id=123
GET /actions/_craft-entry-optimizer/optimized-entry/export?url=https://example.com/my-page
```

Response:

```json
[
  {
    "metadata": {
      "id": 123,
      "siteId": 1
    },
    "title": "Page Title",
    "bodyContent": "<p>Rich text content...</p>",
    "pageBuilder": [
      {
        "type": "contentBlock",
        "title": "Block Title",
        "content": "<p>Block content</p>",
        "backgroundColor": {
          "__handler": "DropdownFieldHandler",
          "__value": { "value": "green", "label": "Green" }
        }
      }
    ],
    "seo": { "seoTitle": "...", "seoDescription": "..." }
  }
]
```

### Import Entry

```
POST /actions/_craft-entry-optimizer/optimized-entry/import
Content-Type: application/json
```

Send the same JSON structure (modified) as the request body. The plugin creates a draft with only the fields that changed.

Response:

```json
{
  "success": true,
  "message": "Draft created successfully with 3 updated field(s)",
  "draftId": 456,
  "entryId": 123,
  "cpEditUrl": "https://example.com/admin/entries/pages/123-draft-name",
  "updatedFields": ["title", "bodyContent", "pageBuilder"]
}
```

## Field Handlers

The plugin uses a handler registry to support different field types. Each handler manages export, import, and change detection.

### Built-in Handlers

| Handler | Field Types | Export Format |
|---------|-------------|---------------|
| **MatrixFieldHandler** | Matrix (+ recursive nesting) | Array of blocks with `__handler` hints |
| **AssetFieldHandler** | Assets | `{id, url, title, alt}` |
| **RelationFieldHandler** | Entries, Categories, Tags, Users | Array of element IDs |
| **LinkFieldHandler** | Craft Link, Hyper, Lenz | `{type, url, label, element, ...}` |
| **DropdownFieldHandler** | Dropdown, RadioButtons, ButtonGroup | `{value, label}` |
| | Checkboxes, MultiSelect | `[{value, label}, ...]` |
| **SeomaticFieldHandler** | SEOmatic (conditional) | `{seoTitle, seoDescription, ...}` |
| **DefaultFieldHandler** | PlainText, Number, Email, Color, Lightswitch, Date, Time | Native value |

### Handler Priority

Handlers are checked by priority (higher = first). Specialized handlers (priority 50) are checked before the default fallback (priority -100).

### Creating Custom Handlers

```php
<?php

namespace MyNamespace;

use craft\base\FieldInterface;
use lameco\craftentryoptimizer\services\fieldhandlers\AbstractFieldHandler;

class MyCustomFieldHandler extends AbstractFieldHandler
{
    public function canHandle(FieldInterface $field): bool
    {
        return $field instanceof MyCustomField;
    }

    public function export(FieldInterface $field, mixed $value): mixed
    {
        return ['customData' => $value->getData()];
    }

    public function import(FieldInterface $field, mixed $value, array $context = []): mixed
    {
        return MyCustomFieldValue::fromArray($value);
    }

    public function getPriority(): int
    {
        return 60;
    }
}
```

Register it in your module or plugin:

```php
use lameco\craftentryoptimizer\Plugin;

Craft::$app->onInit(function() {
    Plugin::getInstance()->fieldHandlerRegistry->register(new MyCustomFieldHandler());
});
```

## Authentication

### API Key

Configure an API key in plugin settings, then use header-based auth:

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
  https://example.com/actions/_craft-entry-optimizer/optimized-entry/export?id=123
```

### Craft Session

When no API key is set and authentication is required, the user must have an active Craft session.

## License

MIT — see [LICENSE](LICENSE).


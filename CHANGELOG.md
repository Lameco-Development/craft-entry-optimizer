# Changelog

## 1.0.2 - 2026-02-20

### Fixed
- `exportBySlug` now correctly resolves slugs containing slashes (e.g. `artikelen/artikel-1`) by querying on URI instead of slug

## 1.0.0 - 2026-02-13

### Added
- Initial release
- Export entries to JSON with full field data
- Import entries from JSON, creating drafts with only changed fields
- Field handler registry system for extensible field type support
- `__handler`/`__value` hint system for reliable Matrix block field type detection
- **Matrix handler**: Recursive nesting support for blocks within blocks
- **Asset handler**: Exports `{id, url, title, alt}`
- **Relation handler**: Supports all `BaseRelationField` types (Entries, Categories, Tags, Users)
- **Link handler**: Supports Craft native Link, Hyper, and Lenz link fields
- **Options handler**: Supports all `BaseOptionsField` types (Dropdown, RadioButtons, ButtonGroup, Checkboxes, MultiSelect)
- **SEOmatic handler**: Conditional on SEOmatic plugin being installed
- **Default handler**: Fallback for simple types (PlainText, Number, Email, Color, Lightswitch, Date, Time)
- Optional API key authentication for both export and import endpoints
- Change detection to identify which fields have been modified

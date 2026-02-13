<?php

namespace lameco\craftentryoptimizer;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use lameco\craftentryoptimizer\models\Settings;
use lameco\craftentryoptimizer\services\EntryExportService;
use lameco\craftentryoptimizer\services\EntryImportService;
use lameco\craftentryoptimizer\services\FieldHandlerRegistry;
use lameco\craftentryoptimizer\services\fieldhandlers\AssetFieldHandler;
use lameco\craftentryoptimizer\services\fieldhandlers\DefaultFieldHandler;
use lameco\craftentryoptimizer\services\fieldhandlers\DropdownFieldHandler;
use lameco\craftentryoptimizer\services\fieldhandlers\LinkFieldHandler;
use lameco\craftentryoptimizer\services\fieldhandlers\MatrixFieldHandler;
use lameco\craftentryoptimizer\services\fieldhandlers\RelationFieldHandler;
use lameco\craftentryoptimizer\services\fieldhandlers\SeomaticFieldHandler;

/**
 * craft-entry-optimizer plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @property-read FieldHandlerRegistry $fieldHandlerRegistry
 * @property-read EntryExportService $entryExportService
 * @property-read EntryImportService $entryImportService
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'fieldHandlerRegistry' => FieldHandlerRegistry::class,
                'entryExportService' => EntryExportService::class,
                'entryImportService' => EntryImportService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Register core field handlers
        $handlers = [
            new MatrixFieldHandler(),
            new AssetFieldHandler(),
            new RelationFieldHandler(),
            new LinkFieldHandler(),
            new DropdownFieldHandler(),
        ];

        // Conditionally register SEOmatic handler if plugin is installed
        $pluginsService = Craft::$app->getPlugins();
        if ($pluginsService->isPluginInstalled('seomatic') && $pluginsService->isPluginEnabled('seomatic')) {
            $handlers[] = new SeomaticFieldHandler();
            Craft::info('SEOmatic plugin detected - registered SEOmatic field handler', __METHOD__);
        }

        // Default handler should always be last (lowest priority)
        $handlers[] = new DefaultFieldHandler();

        $this->fieldHandlerRegistry->registerMultiple($handlers);

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('_craft-entry-optimizer/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }
}

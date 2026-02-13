<?php

namespace lameco\craftentryoptimizer\controllers;

use Craft;
use craft\web\Controller;
use lameco\craftentryoptimizer\Plugin;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Optimized Entry Controller
 * 
 * Handles entry export and import operations using the service layer.
 * All field processing is delegated to EntryExportService and EntryImportService.
 * 
 * This controller provides a thin HTTP layer that delegates all business logic
 * to the service layer, following best practices for MVC architecture.
 */
class OptimizedEntryController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = ['index', 'export', 'import'];
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Get plugin settings
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        // Determine if this action requires authentication
        $requiresAuth = match ($action->id) {
            'export' => $settings->requireAuthExport,
            'import' => $settings->requireAuthImport,
            'index' => false, // Index is always allowed
            default => true,
        };

        if (!$requiresAuth) {
            return parent::beforeAction($action);
        }

        // Check authentication: API key or Craft user
        if ($settings->apiKey) {
            $authHeader = $this->request->getHeaders()->get('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $providedKey = substr($authHeader, 7);
                if (hash_equals($settings->apiKey, $providedKey)) {
                    return parent::beforeAction($action);
                }
            }
        }

        // Fall back to Craft user authentication
        if (Craft::$app->getUser()->getIdentity()) {
            return parent::beforeAction($action);
        }

        throw new UnauthorizedHttpException('Authentication required');
    }

    /**
     * Export action - exports entry data using EntryExportService
     * 
     * Accepts either 'id' or 'url' query parameter to identify the entry.
     * Returns JSON array with single entry export.
     * 
     * @return Response JSON response with export data
     * @throws BadRequestHttpException If neither id nor url provided
     * @throws NotFoundHttpException If entry not found
     */
    public function actionExport(): Response
    {
        $request = Craft::$app->getRequest();
        $entryId = $request->getQueryParam('id');
        $url = $request->getQueryParam('url');

        if (!$entryId && !$url) {
            throw new BadRequestHttpException('Either entry ID or URL is required.');
        }

        $exportService = Plugin::getInstance()->entryExportService;

        try {
            if ($entryId) {
                $exportResult = $exportService->exportById($entryId);
            } else {
                $exportResult = $exportService->exportByUrl($url);
            }

            $result = [$exportResult->toArray()];

            return $this->asJson($result);
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Craft::error('Export failed: ' . $e->getMessage(), __METHOD__);
            throw new BadRequestHttpException('Failed to export entry: ' . $e->getMessage());
        }
    }

    /**
     * Import action - creates draft with changed fields using EntryImportService
     * 
     * Accepts JSON data in request body.
     * Only changed fields are updated in the draft.
     * 
     * @return Response JSON response with import result
     * @throws BadRequestHttpException If JSON invalid or no data provided
     * @throws NotFoundHttpException If entry not found
     */
    public function actionImport(): Response
    {
        $request = Craft::$app->getRequest();
        $json = $request->getRawBody();

        if (empty($json)) {
            throw new BadRequestHttpException('No JSON data provided in request body.');
        }

        $importService = Plugin::getInstance()->entryImportService;

        try {
            $importResult = $importService->importFromJson($json);

            if (!$importResult->success) {
                Craft::$app->getSession()->setError($importResult->message);
                return $this->asJson([
                    'success' => false,
                    'message' => $importResult->message,
                    'errors' => $importResult->errors,
                ]);
            }

            if (empty($importResult->updatedFields)) {
                Craft::$app->getSession()->setNotice($importResult->message);
                return $this->asJson([
                    'success' => true,
                    'message' => $importResult->message,
                    'entryId' => $importResult->entryId,
                    'updatedFields' => [],
                ]);
            }

            Craft::$app->getSession()->setNotice($importResult->message);

            return $this->asJson([
                'success' => true,
                'message' => $importResult->message,
                'draftId' => $importResult->draftId,
                'entryId' => $importResult->entryId,
                'cpEditUrl' => $importResult->cpEditUrl,
                'updatedFields' => $importResult->updatedFields,
            ]);
        } catch (BadRequestHttpException $e) {
            throw $e;
        } catch (NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Craft::error('Import failed: ' . $e->getMessage(), __METHOD__);
            throw new BadRequestHttpException('Failed to import entry: ' . $e->getMessage());
        }
    }

    /**
     * Index action - returns plugin health/status
     * 
     * @return Response JSON response with plugin status
     */
    public function actionIndex(): Response
    {
        return $this->asJson([
            'plugin' => 'Craft Entry Optimizer',
            'version' => '1.0.0',
            'status' => 'active',
            'endpoints' => [
                'export' => 'GET /actions/_craft-entry-optimizer/optimized-entry/export?id=<entryId>',
                'import' => 'POST /actions/_craft-entry-optimizer/optimized-entry/import',
            ],
        ]);
    }
}
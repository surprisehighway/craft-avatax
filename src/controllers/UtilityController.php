<?php
/**
 * Avatax plugin for Craft CMS 3.x
 *
 * Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.
 *
 * @link      http://surprisehighway.com
 * @copyright Copyright (c) 2019 Surprise Highway
 */

namespace surprisehighway\avatax\controllers;

use surprisehighway\avatax\Avatax;
use surprisehighway\avatax\services\SalesTaxService;

use Craft;
use craft\web\Controller;

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 */
class UtilityController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionConnectionTest()
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $settings = $request->getParam('settings');

        $taxService = new SalesTaxService;

        $response = $taxService->connectionTest($settings);

        return $this->asJson($response);
    }

    public function actionLogs()
    {
        $logEntries = Avatax::$plugin->LogService->getLogEntries();

        return $this->renderTemplate('avatax/logs', [
            'logEntries' => $logEntries,
        ]);
    }

    public function actionClearLogs()
    {
        $logEntries = Avatax::$plugin->LogService->clearLogs();

        return $this->redirect('avatax/logs');
    }
}

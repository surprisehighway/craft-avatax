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
use surprisehighway\avatax\services\CertCaptureService;

use Craft;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\Address;

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.6
 */
class JsonController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionValidateAddress()
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $address = new Address();

        // Shared attributes
        $attributes = [
            'attention',
            'title',
            'firstName',
            'lastName',
            'address1',
            'address2',
            'city',
            'zipCode',
            'phone',
            'alternativePhone',
            'businessName',
            'businessTaxId',
            'businessId',
            'countryId',
            'stateValue'
        ];

        foreach ($attributes as $attr) 
        {
            $address->$attr = Craft::$app->getRequest()->getParam($attr);
        }

        //return $this->asJson(['address' => $address]);

        $taxService = new SalesTaxService;

        $response = $taxService->getValidateAddress($address);

        if(!empty($response->validatedAddresses) && isset($response->coordinates))
        {
            return $this->asJson([
                'success' => true,
                'response' => $response
            ]);
        } 
        else
        {           
            return $this->asJson([
                'success' => false,
                'error' => Craft::t('avatax', 'Invalid Address.'),
                'response' => $response,
            ]);
        } 
    }

    /**
     * @return mixed
     */
    public function actionCertCaptureCustomer()
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $customerNumber = Craft::$app->getRequest()->getParam('number');

        $certCaptureService = new CertCaptureService();

        $response = $certCaptureService->getCustomer($customerNumber);

        return $this->asJson($response);
    }
}

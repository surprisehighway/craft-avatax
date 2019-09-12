<?php
/**
 * Avatax plugin for Craft CMS 3.x
 *
 * Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.
 *
 * @link      http://surprisehighway.com
 * @copyright Copyright (c) 2019 Surprise Highway
 */

namespace surprisehighway\avatax\services;

use surprisehighway\avatax\Avatax;
use Avalara\AvaTaxClient;

use Craft;
use craft\base\Component;
use craft\helpers\Json;

use yii\base\Exception;
use yii\log\Logger;

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.7
 */
class CertCaptureService extends Component
{

    // Public Properties
    // =========================================================================

    /**
     * @var settings
     */
    public $settings;

    /**
     * @var url
     */
    public $url = 'https://api.certcapture.com/v2/';

    /**
    * @var auth
    */
    public $auth = [];

    /**
    * @var clientId
    */
    public $clientId;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $settings = Avatax::$plugin->getSettings();

        $this->settings = $settings;

        $this->auth = [
            $settings['certCaptureUsername'], 
            $settings['certCapturePassword']
        ];

        $this->clientId = $settings['certCaptureClientId'];
    }

    /**
     * @return string
     * @return object or boolean
     */
    public function getCustomer($customerNumber)
    {
        if(!$customerNumber)
        {
            return ['success' => false, 'error' => 'Customer Number is required.'];
        }

        if(!$this->auth[0] || !$this->auth[1] || !$this->clientId)
        {
            return ['success' => false, 'error' => 'Invalid CertCapture credentials.'];
        }

        $cacheKey = 'avatax-address-'.md5($customerNumber);
        $cache = Craft::$app->getCache();
        $duration = 86400;

        if($cache->exists($cacheKey))
        {
            return $cache->get($cacheKey);
        }

        $url = 'customers/' . urlencode($customerNumber);

        try {
            $client = $this->createGuzzleClient();
            $options = [
                'auth' => $this->auth,
                'headers' => [
                    'User-Agent' => 'Craft Commerce Avatax',
                    'x-client-id' => $this->clientId,
                    'x-customer-primary-key' => 'customer_number'
                ]
            ];

            $response = $client->request('GET', $url, $options);
            $body = Json::decode((string)$response->getBody());

            $result = ['success' => true, 'response' => $body];
        } 
        catch (\Exception $e) 
        {
            $body = Json::decode((string)$e->getResponse()->getBody());
            $error = $body['error'] ?? $e->getMessage();

            $result = ['success' => false, 'error' => $error];
        }

        $cache->set($cacheKey, $result, $duration);

        return $result;
    }

    public function createGuzzleClient()
    {
        $options = [
            'base_uri' => $this->url
        ];

        return Craft::createGuzzleClient($options);
    }

}
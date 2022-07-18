<?php
/**
 * Avatax plugin for Craft CMS 3.x
 *
 * Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.
 *
 * @link      http://surprisehighway.com
 * @copyright Copyright (c) 2019 Surprise Highway
 */

namespace surprisehighway\avatax\models;

use surprisehighway\avatax\Avatax;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $environment = 'sandbox';

    /**
     * @var string
     */
    public $accountId;

    /**
     * @var string
     */
    public $licenseKey;

    /**
     * @var string
     */
    public $companyCode;

    /**
     * @var string
     */
    public $sandboxAccountId;

    /**
     * @var string
     */
    public $sandboxLicenseKey;

    /**
     * @var string
     */
    public $sandboxCompanyCode;

    /**
     * @var string
     */
    public $shipFromName;

    /**
     * @var string
     */
    public $shipFromStreet1;

    /**
     * @var string
     */
    public $shipFromStreet2;

    /**
     * @var string
     */
    public $shipFromStreet3;

    /**
     * @var string
     */
    public $shipFromCity;

    /**
     * @var string
     */
    public $shipFromState;

    /**
     * @var string
     */
    public $shipFromZipCode;

    /**
     * @var string
     */
    public $shipFromCountry;

    /**
     * @var string
     */
    public $defaultTaxCode = 'P0000000';

    /**
     * @var string
     */
    public $defaultShippingCode = 'FR';

    /**
     * @var string
     */
    public $defaultDiscountCode = 'OD010000';


    /**
     * @var boolean
     */
    public $enableTaxCalculation = true;

    /**
     * @var boolean
     */
    public $enableCommitting = true;

    /**
     * @var boolean
     */
    public $enableAddressValidation = true;

    /**
     * @var boolean
     */
    public $enablePartialRefunds = true;

    /**
     * @var string
     */
    public $certCaptureUsername;

    /**
     * @var string
     */
    public $certCapturePassword;

    /**
     * @var string
     */
    public $certCaptureClientId;

    /**
     * @var boolean
     */
    public $debug = false;



    // Public Methods
    // =========================================================================

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'accountId',
                    'licenseKey',
                    'companyCode',
                    'sandboxAccountId',
                    'sandboxLicenseKey',
                    'sandboxCompanyCode',
                ],
            ],
        ];
    }

    /**
     * @return string
     */
    public function getAccountId()
    {
        return (Craft::parseEnv($this->accountId) ?? '');
    }

    /**
     * @return string
     */
    public function getLicenseKey()
    {
        return (Craft::parseEnv($this->licenseKey) ?? '');
    }

    /**
     * @return string
     */
    public function getCompanyCode()
    {
        return (Craft::parseEnv($this->companyCode) ?? '');
    }

    /**
     * @return string
     */
    public function getSandboxAccountId()
    {
        return (Craft::parseEnv($this->sandboxAccountId) ?? '');
    }

    /**
     * @return string
     */
    public function getSandboxLicenseKey()
    {
        return (Craft::parseEnv($this->sandboxLicenseKey) ?? '');
    }

    /**
     * @return string
     */
    public function getSandboxCompanyCode()
    {
        return (Craft::parseEnv($this->sandboxCompanyCode) ?? '');
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            ['environment', 'string'],
            ['environment', 'default', 'value' => 'sandbox'],
            ['environment', 'in', 'range' => [
                'sandbox', 
                'production'
            ]],
            [
                [
                    'accountId',
                    'licenseKey',
                    'companyCode',
                    'sandboxAccountId',
                    'sandboxLicenseKey',
                    'sandboxCompanyCode',
                ], 
                'string'
            ],
            [
                [
                    'accountId', 
                    'licenseKey', 
                    'companyCode'
                ], 
                'required', 
                'when' => function($model)
                {
                    return $model->environment === 'production';
                }
            ],
            [
                [
                    'sandboxAccountId', 
                    'sandboxLicenseKey', 
                    'sandboxCompanyCode'
                ], 
                'required', 
                'when' => function($model)
                {
                    return $model->environment === 'sandbox';
                }
            ],
            [
                [
                    'defaultTaxCode',
                    'defaultShippingCode',
                    'defaultDiscountCode'
                ],
                'string'
            ],
            ['defaultTaxCode', 'default', 'value' => 'P0000000'],
            ['defaultShippingCode', 'default', 'value' => 'FR'],
            ['defaultDiscountCode', 'default', 'value' => 'OD010000'],
            [
                [
                    'shipFromName',
                    'shipFromStreet1',
                    'shipFromStreet2',
                    'shipFromStreet3',
                    'shipFromCity',
                    'shipFromState',
                    'shipFromZipCode',
                    'shipFromCountry',
                ],
                'string'
            ],
            [
                [
                    'shipFromName',
                    'shipFromStreet1',
                    'shipFromCity',
                    'shipFromState',
                    'shipFromZipCode',
                    'shipFromCountry',
                ],
                'required'
            ],
            [
                [
                    'enableTaxCalculation',
                    'enableCommitting',
                    'enableAddressValidation',
                    'enablePartialRefunds',
                    'debug',
                ], 
                'boolean'
            ],
            ['enableAddressValidation', 'default', 'value' => '0'],
        ];
    }
}

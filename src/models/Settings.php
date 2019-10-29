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

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 */
class Settings extends Model
{
    const ENV_DEBUG = '$AVATAX_DEBUG';
    const ENV_TAX_CALC_ENABLED = '$AVATAX_TAX_CALC_ENABLED';
    const ENV_COMMITTING_ENABLED = '$AVATAX_COMMITTING_ENABLED';
    const ENV_ADDRESS_VALIDATION_ENABLED = '$AVATAX_ADDRESS_VALIDATION_ENABLED';
    const ENV_PARTIAL_REFUNDS_ENABLED = '$AVATAX_PARTIAL_REFUNDS_ENABLED';
    const ENV_ENVIRONMENT = '$AVATAX_ENV';

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

    /**
     * @inheritdoc
     */
    public function rules()
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
        ];
    }

    /**
     * Get the value of accountId
     *
     * @return  string
     */ 
    public function getAccountId()
    {
        return Craft::parseEnv($this->accountId);
    }

    /**
     * Get the value of licenseKey
     *
     * @return  string
     */ 
    public function getLicenseKey()
    {
        return Craft::parseEnv($this->licenseKey);
    }

    /**
     * Get the value of companyCode
     *
     * @return  string
     */ 
    public function getCompanyCode()
    {
        return Craft::parseEnv($this->companyCode);
    }

    /**
     * Get the value of sandboxAccountId
     *
     * @return  string
     */ 
    public function getSandboxAccountId()
    {
        return Craft::parseEnv($this->sandboxAccountId);
    }

    /**
     * Get the value of sandboxLicenseKey
     *
     * @return  string
     */ 
    public function getSandboxLicenseKey()
    {
        return Craft::parseEnv($this->sandboxLicenseKey);
    }

    /**
     * Get the value of sandboxCompanyCode
     *
     * @return  string
     */ 
    public function getSandboxCompanyCode()
    {
        return Craft::parseEnv($this->sandboxCompanyCode);
    }

    /**
     * Get the value of shipFromName
     *
     * @return  string
     */ 
    public function getShipFromName()
    {
        return Craft::parseEnv($this->shipFromName);
    }

    /**
     * Get the value of shipFromStreet1
     *
     * @return  string
     */ 
    public function getShipFromStreet1()
    {
        return Craft::parseEnv($this->shipFromStreet1);
    }

    /**
     * Get the value of shipFromStreet2
     *
     * @return  string
     */ 
    public function getShipFromStreet2()
    {
        return Craft::parseEnv($this->shipFromStreet2);
    }

    /**
     * Get the value of shipFromStreet3
     *
     * @return  string
     */ 
    public function getShipFromStreet3()
    {
        return Craft::parseEnv($this->shipFromStreet3);
    }

    /**
     * Get the value of shipFromCity
     *
     * @return  string
     */ 
    public function getShipFromCity()
    {
        return Craft::parseEnv($this->shipFromCity);
    }

    /**
     * Get the value of shipFromState
     *
     * @return  string
     */ 
    public function getShipFromState()
    {
        return Craft::parseEnv($this->shipFromState);
    }

    /**
     * Get the value of shipFromZipCode
     *
     * @return  string
     */ 
    public function getShipFromZipCode()
    {
        return Craft::parseEnv($this->shipFromZipCode);
    }

    /**
     * Get the value of shipFromCountry
     *
     * @return  string
     */ 
    public function getShipFromCountry()
    {
        return Craft::parseEnv($this->shipFromCountry);
    }

    /**
     * Get the value of defaultTaxCode
     *
     * @return  string
     */ 
    public function getDefaultTaxCode()
    {
        return Craft::parseEnv($this->defaultTaxCode);
    }

    /**
     * Get the value of defaultShippingCode
     *
     * @return  string
     */ 
    public function getDefaultShippingCode()
    {
        return Craft::parseEnv($this->defaultShippingCode);
    }

    /**
     * Get the value of defaultDiscountCode
     *
     * @return  string
     */ 
    public function getDefaultDiscountCode()
    {
        return Craft::parseEnv($this->defaultDiscountCode);
    }

    /**
     * Get the value of debug
     *
     * @return  boolean
     */ 
    public function getDebug()
    {
        $envValue = Craft::parseEnv(self::ENV_DEBUG);
        if ($envValue !== self::ENV_DEBUG) {
            return $envValue;
        }
        return $this->debug;
    }

    /**
     * Get the value of enableTaxCalculation
     *
     * @return  boolean
     */ 
    public function getEnableTaxCalculation()
    {
        $envValue = Craft::parseEnv(self::ENV_TAX_CALC_ENABLED);
        if ($envValue !== self::ENV_TAX_CALC_ENABLED) {
            return $envValue;
        }
        return $this->enableTaxCalculation;
    }

    /**
     * Get the value of enableCommitting
     *
     * @return  boolean
     */ 
    public function getEnableCommitting()
    {
        $envValue = Craft::parseEnv(self::ENV_COMMITTING_ENABLED);
        if ($envValue !== self::ENV_COMMITTING_ENABLED) {
            return $envValue;
        }
        return $this->enableCommitting;
    }

    /**
     * Get the value of enableAddressValidation
     *
     * @return  boolean
     */ 
    public function getEnableAddressValidation()
    {
        $envValue = Craft::parseEnv(self::ENV_ADDRESS_VALIDATION_ENABLED);
        if ($envValue !== self::ENV_ADDRESS_VALIDATION_ENABLED) {
            return $envValue;
        }
        return $this->enableAddressValidation;
    }

    /**
     * Get the value of enablePartialRefunds
     *
     * @return  boolean
     */ 
    public function getEnablePartialRefunds()
    {
        $envValue = Craft::parseEnv(self::ENV_PARTIAL_REFUNDS_ENABLED);
        if ($envValue !== self::ENV_PARTIAL_REFUNDS_ENABLED) {
            return $envValue;
        }
        return $this->enablePartialRefunds;
    }

    /**
     * Get the value of environment
     *
     * @return  string
     */ 
    public function getEnvironment()
    {
        $envValue = Craft::parseEnv(self::ENV_ENVIRONMENT);
        if ($envValue !== self::ENV_ENVIRONMENT) {
            return $envValue;
        }
        return $this->environment;
    }

    public function isManuallySet($var)
    {
        return Craft::parseEnv($var) !== $var;
    }
}

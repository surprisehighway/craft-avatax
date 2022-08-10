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
use Avalara\AddressValidationInfo;

use Craft;
use craft\base\Component;

use craft\commerce\Plugin as Commerce;
use craft\elements\Address;
use craft\commerce\models\OrderAdjustment;
use craft\commerce\models\Transaction;
use craft\commerce\elements\Order;
use craft\commerce\helpers\Currency;

use yii\base\Exception;
use yii\log\Logger;

/**
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 */
class SalesTaxService extends Component
{

    // Public Properties
    // =========================================================================

    /**
     * @var settings
     */
    public $settings;

    /**
     * @var boolean
     */
    public $debug = false;

    /**
     * @var string The type of commit (order or invoice)
     */
    public $type;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $settings = Avatax::$plugin->getSettings();

        $this->settings = $settings;
        $this->debug = $settings->debug;
    }

    /**
     * @param array $settings
     * @return object or boolean
     *
     *  From any other plugin file, call it like this:
     *  Avatax::getInstance()->SalesTaxService->connectionTest()
     *
     * Creates a new client with the given settings and tests the connection.
     * See https://developer.avalara.com/api-reference/avatax/rest/v2/methods/Utilities/Ping/
     *
     */
    public function connectionTest($settings)
    {
        $client = $this->createClient($settings);

        return $client->ping();
    }

    /**
     * @param object Order $order
     * @return object
     *
     *  From any other plugin file, call it like this:
     *  Avatax::getInstance()->SalesTaxService->createSalesOrder()
     *
     * Creates a new sales order - a temporary transaction to determine the tax rate.
     * See "Sales Orders vs Sales Invoices" https://developer.avatax.com/blog/2016/11/04/estimating-tax-with-rest-v2/
     * See also: https://developer.avatax.com/avatax/use-cases/
     *
     */
    public function createSalesOrder(Order $order)
    {    
        // check for form overrides to plugin settings and bail if disabled
        $disabled = $this->parseOverrideParam('avatax_disable_tax_calculation');
        $enabled = $this->parseOverrideParam('avatax_force_tax_calculation');

        if((!$this->settings->enableTaxCalculation || $disabled) && !$enabled)
        {
            Avatax::info(__FUNCTION__.'(): Tax Calculation is disabled.');

            return false;
        }

        $this->type = 'order';

        $client = $this->createClient();

        $tb = new \Avalara\TransactionBuilder(
            $client, $this->getCompanyCode(),
            \Avalara\DocumentType::C_SALESORDER,
            $this->getCustomerCode($order)
        );

        $totalTax = $this->getTotalTax($order, $tb);

        return $totalTax;
    }

    /**
     * @param object Order $order
     * @return object
     *
     *  From any other plugin file, call it like this:
     *  Avatax::getInstance()->SalesTaxService->createSalesInvoice()
     *
     * Creates and commits a new sales invoice
     * See "Sales Orders vs Sales Invoices" https://developer.avatax.com/blog/2016/11/04/estimating-tax-with-rest-v2/
     * See also: https://developer.avatax.com/avatax/use-cases/
     *
     */
    public function createSalesInvoice(Order $order)
    {
        if(!$this->settings->enableCommitting)
        {

            Avatax::info(__FUNCTION__.'(): Document Committing is disabled.');

            return false;
        }

        $this->type = 'invoice';

        $client = $this->createClient();

        $tb = new \Avalara\TransactionBuilder(
            $client, $this->getCompanyCode(),
            \Avalara\DocumentType::C_SALESINVOICE,
            $this->getCustomerCode($order)
        );

        $tb->withCommit();

        $totalTax = $this->getTotalTax($order, $tb);

        return $totalTax;
    }

    /**
     * @param float $amount amount of the refund
     * @param object Transaction $transaction
     * @return boolean
     *
     * Handle a return event.
     *
     */
    public function handleRefund($amount, Transaction $transaction)
    {
        // catch rounding issues so we can just issue a full refund if possible
        $paymentCurrency = Commerce::getInstance()->getPaymentCurrencies()->getPaymentCurrencyByIso($transaction->order->paymentCurrency);
        $refundAmount  = Currency::round($amount, $paymentCurrency);
        $paymentAmount = Currency::round($transaction->paymentAmount, $paymentCurrency);

        if($refundAmount < $paymentAmount) {
            return $this->refundPartialTransaction($amount, $transaction);
        }

        return $this->refundFullTransaction($amount, $transaction);
    }

    /**
     * @param float $amount amount of the refund
     * @param object Transaction $transaction
     * @return boolean
     *
     * Refund a committed sales invoice
     * See "Refund Transaction" https://developer.avalara.com/api-reference/avatax/rest/v2/methods/Transactions/RefundTransaction
     *
     */
    public function refundFullTransaction($amount, Transaction $transaction)
    {
        $client = $this->createClient();

        $order = $transaction->order;

        $request = array(
            'companyCode' => $this->getCompanyCode(),
            'transactionCode' => $this->getTransactionCode($order)
        );

        $model = array(
            'refundTransactionCode' => $request['transactionCode'].'-refund',
            'refundType' => \Avalara\RefundType::C_FULL,
            'refundDate' => date('Y-m-d'),
            'referenceCode' => 'Refund from Craft Commerce'
        );

        extract($request);

        $response = $client->refundTransaction(
            $companyCode,
            $transactionCode,
            null,
            null,
            'true',
            $model
        );

        if($this->debug)
        {
            $request = array_merge($request, $model);

            Avatax::info('\Avalara\Client->refundTransaction(): ', ['request' =>json_encode($request), 'response' => json_encode($response)]);
        }

        if(isset($response->status) && $response->status === 'Committed')
        {
            Avatax::info('Transaction Code '.$transactionCode.' was successfully refunded (full).');

            return true;
        }

        Avatax::error('Transaction Code '.$transactionCode.' could not be refunded.', ['request' => json_encode($request), 'response' => json_encode($response)]);

        return false;
    }

    /**
     * @param float $amount amount of the refund
     * @param object Transaction $transaction
     * @return boolean
     *
     * Refund a specific amount to a customer by creating and committing a new Return Invoice.
     * Note that this is not tied to a specific order so tax refund is determined by the customer location and exemption status.
     * See "Create Transaction" https://developer.avalara.com/api-reference/avatax/rest/v2/methods/Transactions/CreateTransaction/
     * See https://community.avalara.com/avalara/topics/refund-transaction-api?topic-reply-list[settings][filter_by]=all&topic-reply-list[settings][reply_id]=19097602#reply_19097602
     */
    public function refundPartialTransaction($amount, Transaction $transaction)
    {
        if(!$this->settings->enablePartialRefunds)
        {
            Avatax::info(__FUNCTION__.'(): Sending partial refunds to AvaTax is disabled.');

            return false;
        }

        $order = $transaction->order;

        // if no tax was recorded do not send to Avalara to calculate
        if( !($order->getTotalTax() > 0)) {
            return false;
        }

        // check for previous refunds and increment suffix to avoid duplicate ids
        $count = 0;
        foreach($transaction->order->getTransactions() as $childTransaction)
        {
            if($childTransaction->type === 'refund')
            {
                $count++;
            }
        }

        $suffix = ($count > 0) ? '-'.$count : '';

        // begin request
        $client = $this->createClient();

        $tb = new \Avalara\TransactionBuilder(
            $client, $this->getCompanyCode(),
            \Avalara\DocumentType::C_RETURNINVOICE,
            $this->getCustomerCode($order)
        );

        $tb->withLineItem([
            ['amount' => -$amount]
        ])->withTransactionCode(
            $this->getTransactionCode($order).'-refund'.$suffix
        )->withReferenceCode(
            'Partial refund from Craft Commerce'
        )->withAddress(
            'singleLocation',
            $order->shippingAddress->addressLine1,
            NULL,
            NULL,
            $order->shippingAddress->locality,
            $order->shippingAddress->administrativeArea,
            $order->shippingAddress->postalCode,
            $order->shippingAddress->countryCode
        )->withCommit();

        // add entity/use code if set for the customer
        if(!is_null($order->getCustomer()))
        {
            if($this->getFieldValue('avataxCustomerUsageType', $order->getCustomer()))
            {
                $tb = $tb->withEntityUseCode($this->getFieldValue('avataxCustomerUsageType', $order->getCustomer()));
            }
        }

        $response = $tb->create();

        if($this->debug)
        {
            // workaround to save the model as array for debug logging
            $m = $tb; $model = $m->createAdjustmentRequest(null, null)['newTransaction'];
            Avatax::info('\Avalara\TransactionBuilder->create(): ', ['request' =>json_encode($model), 'response' => json_encode($response)]);
        }

        if(isset($response->status) && $response->status === 'Committed')
        {
            Avatax::info('Transaction Code '.$this->getTransactionCode($order).' was successfully refunded (partial).');

            return true;
        }

        Avatax::error('Transaction Code '.$this->getTransactionCode($order).' could not be refunded.');

        return false;
    }

    /**
     * @param object $address Address model craft\elements\Address
     * @return boolean
     *
     *  From any other plugin file, call it like this:
     *  Avatax::getInstance()->SalesTaxService->validateAddress()
     *
     * Validates and address
     * See: https://developer.avalara.com/api-reference/avatax/rest/v2/methods/Addresses/ResolveAddressPost/
     *
     */
    public function validateAddress(Address $address)
    {
        if(!$this->settings['enableAddressValidation'])
        {
            Avatax::info(__FUNCTION__.'(): Address validation is disabled.');

            return false;
        }

        $response = $this->getValidateAddress($address);

        if(!empty($response->validatedAddresses) && isset($response->coordinates))
        {
            return true;
        }

        // Request failed
        Avatax::error('Address validation failed.', ['request' => json_encode($address), 'response' => json_encode($response)]);

        return false;
    }

    /**
     * @param object $address Avatax Address model Avalara\AvaTaxClient\AddressValidationInfo
     * @return object
     */
    function getValidateAddress(Address $address)
    {
        $signature = $this->getAddressSignature($address);
        $cacheKey = 'avatax-address-'.$signature;
        $cache = Craft::$app->getCache();

        // Check if validated address has been cached, if not make api call.
        $response = $cache->get($cacheKey);
        //if($response) Avatax::info('Cached address found: '.$cacheKey);

        if(!$response)
        {
            // Convert commerce address to avatax address model
            $request = new AddressValidationInfo();

            $request->textCase = 'Mixed';
            $request->line1 = $address->addressLine1;
            $request->line2 = $address->addressLine2;
            $request->line3 = '';
            $request->city = $address->locality;
            $request->region = $address->administrativeArea;
            $request->country = $address->countryCode;
            $request->postalCode = $address->postalCode;
            $request->latitude = '';
            $request->longitude = '';

            // Make avatax api request
            $client = $this->createClient();

            $response = $client->resolveAddress($request->line1, $request->line2, $request->line3, $request->city, $request->region, $request->postalCode, $request->country, $request->textCase, $request->latitude, $request->longitude);

            $cache->set($cacheKey, $response);

            Avatax::info('\Avalara\AvaTaxClient->resolveAddress():', ['request' => json_encode($request), 'response' => json_encode($response)]);
        }

        return $response;
    }


    // Private Methods
    // =========================================================================

    /**
     * @return string $companyCode
     */
    private function getCompanyCode()
    {
        if($this->settings['environment'] === 'production')
        {
            $companyCode = $this->settings->getCompanyCode();
        }

        if($this->settings['environment'] === 'sandbox')
        {
            $companyCode =$this->settings->getSandboxCompanyCode();
        }

        return $companyCode;
    }

    /**
     * @return string $customerCode
     */
    private function getCustomerCode($order)
    {

        $customerCode = (!empty($order->email)) ? $order->email : 'GUEST';

        // Override value from a logged-in User field if available
        if(!is_null($order->getCustomer()))
        {
            if($this->getFieldValue('avataxCustomerCode', $order->getCustomer()))
            {
                $customerCode = $this->getFieldValue('avataxCustomerCode', $order->getCustomer());
            }
        }

        // Override value from an order field if available
        if($this->getFieldValue('avataxCustomerCode', $order))
        {
            $customerCode = $this->getFieldValue('avataxCustomerCode', $order);
        }

        return $customerCode;
    }

    /**
     * @return string|null
     *
     * Check for override value in a plaintext or dropdown field.
     */
    private function getFieldValue($handle, $element)
    {
        if($element->fieldValues !== null && array_key_exists($handle, $element->fieldValues))
        {
            $field = $element->fieldValues[$handle];
            $value = isset($field->value) ? $field->value : $field;

            if(is_string($value) && !empty($value))
            {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return string $transactionCode
     *
     * Use the prefixed order number as the document code so that
     * we can reference it again for subsequent calls if needed.
     */
    private function getTransactionCode($order)
    {
        $prefix = 'cr_';

        return $prefix.$order->number;
    }

    /**
     * @return object $client
     */
    private function createClient($settings = null)
    {
        $settings = ($settings) ? $settings : $this->settings;

        $pluginName = 'Craft Commerce '.Avatax::$plugin->name;
        $pluginVersion = Avatax::$plugin->version;
        $machineName = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : 'localhost';

        if($settings['environment'] === 'production')
        {
            $accountId = (Craft::parseEnv($settings['accountId'])) ?? '';
            $licenseKey = (Craft::parseEnv($settings['licenseKey'])) ?? '';

            if(!empty($accountId) && !empty($licenseKey))
            {
                // Create a new client
                $client = new AvaTaxClient($pluginName, $pluginVersion, $machineName, 'production');

                $client->withLicenseKey($accountId, $licenseKey);

                return $client;
            }
        }

        if($settings['environment'] === 'sandbox')
        {
            $sandboxAccountId = (Craft::parseEnv($settings['sandboxAccountId'])) ?? '';
            $sandboxLicenseKey = (Craft::parseEnv($settings['sandboxLicenseKey'])) ?? '';

            if(!empty($sandboxAccountId) && !empty($sandboxLicenseKey))
            {
                // Create a new client
                $client = new AvaTaxClient($pluginName, $pluginVersion, $machineName, 'sandbox');

                $client->withLicenseKey($sandboxAccountId, $sandboxLicenseKey);

                return $client;
            }
        }

        // Don't have credentials
        Avatax::error('Avatax Account Credentials not found. Check the plugin settings.');
    }

    /**
     * @param object Order $order
     * @param object Avatax\TransactionBuilder $transaction
     * @return object
     *
     */
    private function getTotalTax($order, $transaction)
    {
        if($this->settings['enableAddressValidation'] && $order->shippingAddress)
        {
            // Make sure we have a valid address before continuing.
            if($this->validateAddress($order->shippingAddress) === false)
            {
                return false;
            }
        }

        $defaultTaxCode = $this->settings['defaultTaxCode'];
        $defaultShippingCode = $this->settings['defaultShippingCode'];
        $defaultDiscountCode = $this->settings['defaultDiscountCode'];

        $t = $transaction->withTransactionCode(
                $this->getTransactionCode($order)
            )
            ->withAddress(
                'shipFrom',
                $this->settings['shipFromStreet1'],
                $this->settings['shipFromStreet2'],
                $this->settings['shipFromStreet3'],
                $this->settings['shipFromCity'],
                $this->settings['shipFromState'],
                $this->settings['shipFromZipCode'],
                $this->settings['shipFromCountry']
            )
            ->withAddress(
                'shipTo',
                $order->shippingAddress->addressLine1,
                NULL,
                NULL,
                $order->shippingAddress->locality,
                $order->shippingAddress->administrativeArea,
                $order->shippingAddress->postalCode,
                $order->shippingAddress->countryCode
            );

        // Add each line item to the transaction
        foreach ($order->lineItems as $lineItem) {

            // Our product has the avatax tax category specified
            if($lineItem->taxCategory->handle === 'avatax'){

                $taxCode = $defaultTaxCode;

                if($this->getFieldValue('avataxTaxCode', $lineItem->purchasable->product)) {
                    $taxCode = $this->getFieldValue('avataxTaxCode', $lineItem->purchasable->product);
                }

                $itemCode = $lineItem->id;

                if(!empty($lineItem->sku)) {
                    $itemCode = $lineItem->sku;
                }

               // amount, $quantity, $itemCode, $taxCode)
               $t = $t->withLine(
                    $lineItem->subtotal,    // Total amount for the line item
                    $lineItem->qty,         // Quantity
                    $itemCode,              // Item Code
                    $taxCode                // Tax Code - Default or Custom Tax Code.
                );

               // add human-readable description to line item
               $t = $t->withLineDescription($lineItem->purchasable->product->title);
           }
        }

        // Add each discount line item
        $discountCode = $defaultDiscountCode;

        foreach ($order->adjustments as $adjustment) {

            /** @var OrderAdjustment $adjustment */

            if($adjustment->type === 'discount') {

                // if the discount is for a specific lineItem make sure the discountCode
                // for this adjustment matches the lineItem tax code
                if ($adjustmentLineItem = $adjustment->getLineItem())
                {
                    $discountCode = $defaultTaxCode;

                    // check to see if there is an Avatax Tax Code override specified
                    if($this->getFieldValue('avataxTaxCode', $lineItem->purchasable->product))
                    {
                        $discountCode = $this->getFieldValue('avataxTaxCode', $lineItem->purchasable->product);
                    }
                }

                $t = $t->withLine(
                    $adjustment->amount, // Total amount for the line item
                    1,                   // quantity
                    $adjustment->name,   // Item Code
                    $discountCode        // Tax Code
                );

                // add description to discount line item
                $t = $t->withLineDescription($adjustment->description);
            }
        }

        // Add shipping cost as line-item
        $shippingTaxCode = $defaultShippingCode;

        $t = $t->withLine(
            $order->getTotalShippingCost(),  // total amount for the line item
            1,                                              // quantity
            "FREIGHT",                                      // Item Code
            $shippingTaxCode                                // Tax code for freight (Shipping)
        );

        // add description to shipping line item
        $t = $t->withLineDescription('Total Shipping Cost');

        // add entity/use code if set for a logged-in User
        if(!is_null($order->getCustomer()))
        {
            if($this->getFieldValue('avataxCustomerUsageType', $order->getCustomer()))
            {
                $t = $t->withEntityUseCode($this->getFieldValue('avataxCustomerUsageType', $order->getCustomer()));
            }
        }

        // workaround to save the model as array for debug logging
        $m = $t; $model = $m->createAdjustmentRequest(null, null)['newTransaction'];

        $signature = $this->getOrderSignature($order);
        $cacheKey = 'avatax-'.$this->type.'-'.$signature;
        $cache = Craft::$app->getCache();

        // Check if tax request has been cached when not committing, if not make api call.
        $response = $cache->get($cacheKey);
        //if($response) Avatax::info('Cached order found: '.$cacheKey);

        if(!$response || $this->type === 'invoice')
        {
            $response = $t->create();

            $cache->set($cacheKey, $response);

            if($this->debug)
            {
                Avatax::info('\Avalara\TransactionBuilder->create() '.$this->type.':', ['request' => json_encode($model), 'response' => json_encode($response)]);
            }
        }

        if(isset($response->totalTax))
        {
            return $response->totalTax;
        }

        // Log error
        Avatax::error('Request to avatax.com failed', ['request' => json_encode($model), 'response' => json_encode($response)]);

        return false;
    }

    /**
     * Returns a hash derived from the order's properties.
     */
    private function getOrderSignature(Order $order)
    {
        $orderNumber = $order->number;
        $shipping = $order->getTotalShippingCost();
        $discount = $order->getTotalDiscount();
        $tax = $order->getTotalTax();
        $total = $order->totalPrice;

        $addressLine1 = $order->shippingAddress->addressLine1;
        $addressLine2 = $order->shippingAddress->addressLine2;
        $city = $order->shippingAddress->locality;
        $state = $order->shippingAddress->administrativeArea;
        $zipCode = $order->shippingAddress->postalCode;
        $country = $order->shippingAddress->countryCode;
        $address = $addressLine1.$addressLine2.$city.$state.$zipCode.$country;

        $lineItems = '';
        foreach ($order->lineItems as $lineItem)
        {
            $itemCode = $lineItem->id;
            $subtotal = $lineItem->subtotal;
            $qty = $lineItem->qty;
            $lineItems .= $itemCode.$subtotal.$qty;
        }

        return md5($orderNumber.$shipping.$discount.$tax.$total.$lineItems.$address);
    }

    /**
     * Returns a hash derived from the address.
     */
    private function getAddressSignature(Address $address)
    {
        $addressLine1 = $address->addressLine1;
        $addressLine2 = $address->addressLine2;
        $city = $address->locality;
        $state = $address->administrativeArea;
        $zipCode = $address->postalCode;
        $country = $address->countryCode;

        return md5($addressLine1.$addressLine2.$city.$state.$zipCode.$country);
    }

    /**
     * Parse override form parameters
     */
    private function parseOverrideParam($param)
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return false;
        }

        $value = Craft::$app->getRequest()->getParam($param);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

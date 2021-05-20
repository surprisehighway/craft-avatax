<?php
/**
 * Avatax plugin for Craft CMS 3.x
 *
 * Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.
 *
 * @link      http://surprisehighway.com
 * @copyright Copyright (c) 2019 Surprise Highway
 */

/**
 * Avatax config.php
 *
 * This file exists only as a template for the Avatax settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'avatax.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
	'*' => [
		// The address you will be posting from.
		'shipFromName'    => 'John Doe',
		'shipFromStreet1' => '201 E Randolph St',
		'shipFromStreet2' => '',
		'shipFromStreet3' => '',
		'shipFromCity'    => 'Chicago',
		'shipFromState'   => 'IL',
		'shipFromZipCode' => '60601',
		'shipFromCountry' => 'US',

		// The default Avalara Tax Code to use for Products.
		'defaultTaxCode' => 'P0000000',

		// The default Avalara Tax Code to use for Shipping.
		'defaultShippingCode' => 'FR',

		// The default Avalara Tax Code to use for Discounts.
		'defaultDiscountCode' => 'OD010000',

		// Production account information from ENV.
		'accountId'          => '$AVATAX_ACCOUNT_ID',
		'licenseKey'         => '$AVATAX_LICENSE_KEY',
		'companyCode'        => '$AVATAX_COMPANY_CODE',

		// Sandbox account information from ENV.
		'sandboxAccountId'   => '$AVATAX_SANDBOX_ACCOUNT_ID',
		'sandboxLicenseKey'  => '$AVATAX_SANDBOX_LICENSE_KEY',
		'sandboxCompanyCode' => '$AVATAX_SANDBOX_COMPANY_CODE',

		// Environment - 'production' or 'sandbox'.
		'environment' => 'sandbox',

		// AvaTax options - true or false
		'enableTaxCalculation'    => true,
		'enableCommitting'        => true,
		'enableAddressValidation' => false,
		'enablePartialRefunds'    => true,
		
		// Enable debugging - true or false
		'debug' => true,
	],

	'production' => [
		// Environment - 'production' or 'sandbox'.
		'environment' => 'production',

		// Enable debugging - true or false
		'debug' => false,
	],

];

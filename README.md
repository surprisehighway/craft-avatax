# Avatax plugin for Craft CMS 3.x

Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.

This plugin is in beta and bugs may be present. Please document any issues you encounter at our [Github Issues](https://github.com/surprisehighway/craft-avatax/issues) page.

## Requirements

This plugin requires Craft CMS 3.1.0 or later and Craft Commerce 2.0 or later.

> Related: The Craft 2.x Commerce 1.x [version](https://github.com/surprisehighway/craft-avataxtaxadjuster) of this plugin is no longer actively maintained but is still supported.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require surprisehighway/avatax

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Avatax.

## Configuring AvaTax Account Connection

1. Visit the settings page at Settings → Avatax
2. Enter your the Account ID, License Key, and Company code credentials for each environment.
3. Selecting *Sandbox* or *Production* will enable the chosen environment.
4. Click the *Test Connection* button to verify your connection.
5. Click the *Save* button to save your settings.

![Account Settings](resources/img/plugin-settings.png)

## Configuring AvaTax Ship From Origin

1. Specify a valid origin address to ship from.
2. Click the *Save* button to save your settings.

![Origin](resources/img/plugin-origin.png)

## Configuring AvaTax Plugin Options

1. Visit the settings page at Settings → Avatax
2. *Tax Calculation Enabled* - enable or disable tax calculation independantly of other settings.
3. *Committing Enabled* - enable or disable document committing.
4. *Address Validation Enabled* - enable or disable Avalara's address verification.
5. *Debugging enabled* - while setting up and testing enable debugging to log all API interactions. Be sure to disable once live.
6. Click the *Save* button to save your settings.

![Plugin Options](resources/img/plugin-options.png)

## Using AvaTax

1. Visit Commerce → Settings → Tax Categories. A tax category with the handle "avatax" should exist, if not, create one.

	![Product Tax Categories](resources/img/tax-categories.png)

2. Click the Avatax Tax Category. Check the product type(s) the Avalara Tax Rates will be available to.

	![Avatax Tax Category Settings](resources/img/tax-category-settings.png)

After completing the installation and configuration, AvaTax will calculate and apply sales tax to all orders with a valid shipping address for products with the Avatax tax category selected.

![Avatax Tax Category Settings](resources/img/product-category.png)

## Tax Codes

*E.g. 'P0000000' - Tangible personal property (TPP)*.

You can set the default [Avalara Tax Code](https://taxcode.avatax.avalara.com/) by setting the Default Tax Code value in the plugin settings. This is the default tax code that will get sent to Avalara for all products.

![Defaults](resources/img/plugin-defaults.png)

You can also set a specific Tax Code for each product by adding a custom field to your Products.

#### To set up the product field:

1. Visit Settings → Fields. You should see a field named "AvaTax Tax Code" that was created during plugin installation. If not create, one. Note that the field "Name" can be anything you'd like, e.g. "AvaTax Tax Code" or "Product Tax Code", but the field "Handle" must match `avataxTaxCode` and is case sensitive.
2. Visit Commerce → Settings → Product Types and click the name of your Product Type.
2. Click the *Product Fields* tab.
3. Add the AvaTax Tax Code field and save.

![Fields](resources/img/plugin-fields.png)

In your product entries you can now enter any text to send along as the AvaTax Tax Code. If this field does not exist or is left empty the default tax code setting in your config file will be used.

![Product Field](resources/img/product-field.png)

> Hint: By default the field is plain text, but you could change it to a dropdown with pre-configured values for your use case as long as the handle stays the same.

## Shipping Codes

*E.g. 'FR' - Shipping Only - common carrier - FOB destination.*

Shipping charges are sent as a separate line item to AvaTax. You can set your default [Avalara Tax Code](https://taxcode.avatax.avalara.com/) for shipping charges by setting the Default Shipping Code in the plugin settings.

![Defaults](resources/img/plugin-defaults.png)

## Tax-Exempt Customers

You can specify a customer to be exempt from tax by adding a custom field to your User settings which is used to specify an [Avalara Entity/Use Code](https://help.avalara.com/000_Avalara_AvaTax/Exemption_Reason_Matrices_for_US_and_Canada).

#### To set up the User field:

1. Visit Settings → Fields. You should see a field named "AvaTax Customer Usage Type" that was created during plugin installation. If not create, one. Note that the field "Name" can be anything you'd like, e.g. "AvaTax Customer Usage Type" or "Entity/Use Code", but the field "Handle" must match `avataxCustomerUsageType` and is case sensitive.
2. Visit Settings → Users → Fields.
3. Add the AvaTax Customer Usage Type field and save.

![Fields](resources/img/plugin-fields.png)

In your User accounts you can now set an Entity/Use Code to send to Avalara. It is up to you how you implement this for your users if you allow them to edit their own profiles on the front-end, but this will most likely remain an administrative task in most cases.

This necessarily requires a registered User to be logged in during checkout, not guest checkouts.

![Dropdown](resources/img/user-field.png)

> Hint: By default this dropdown field contains all the default Avalara Entity/Use Codes but you can edit the options to customize for own use case or if you’ve set up custom codes via the AvaTax website.

## Promotions

Promotions are supported. For Sales, the sale price is simply sent to Alavara as the Line Item amount. Discounts are sent as a separate line item using the *Default Discount Code* plugin setting as the Avalara Tax Code.

## Refunds

Craft Commerce supports refunds for completed transactions if the [payment gateway](https://craftcommerce.com/support/which-payment-gateways-do-you-support) supports refunds. If refunds are supported for an order Commerce displays a "Refund" button in the order’s Transaction history. 

As of Commerce 2.0 partial refunds can be initiated multiple times from the admin control panel. Triggering a full refund for the exact amount of the original order will issue a new Return Invoice for full amount of the corresponding AvaTax transaction. Be aware that triggering a partial refund will issue a new Return Invoice for the partial amount to the corresponding AvaTax *customer* but is not tied to the original transaction. This is because AvaTax only issues refunds on a transaction in full, for specific line items, or as a percentage.


## Config Overrides

You can use Craft's [plugin config file](https://docs.craftcms.com/v3/extend/plugin-settings.html#overriding-setting-values) support to override many of the plugin settings in the control panel. This is handy to "lock down" certain settings, and also to provide [per-environment settings](https://docs.craftcms.com/v3/config/environments.html#config-files).

1. Copy `config.php` from the `avataxtax` directory to your craft/config folder and rename it to `avatax.php`
2. Update values in `avatax.php` and save.

## AvaTax Plugin Roadmap

Some things to do, and ideas for potential features:

* Config settings for default tax codes at the Product Type level.

---

Brought to you by [Surprise Highway](http://surprisehighway.com)

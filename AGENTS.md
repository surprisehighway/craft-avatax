# Agent guidance: craft-avatax

A Craft CMS 5 / Craft Commerce 5 plugin (`surprisehighway/craft-avatax`, PHP ^8.2) that calculates sales tax through Avalara AvaTax using the `avalara/avataxclient` SDK.

## Development

This repo is only the plugin. It has no tests, linter, build step or `vendor/` directory, and it does not run on its own. To work on it, install it into a Craft 5 + Commerce 5 project as a Composer path repository. Then use `php craft plugin/install avatax` in that project and check behavior in the control panel or the storefront.

- Branch from `master`. `develop` is stale (last updated at v2.1.5) and should not be used as a base.
- Release process: bump `version` in `composer.json` and add an entry to `CHANGELOG.md`. Changelog entries are written at release time, not in feature branches.
- `src/assetbundles/avatax/dist/` is committed by hand. Nothing builds it.
- `src/config.php` is only a template that users copy to `config/avatax.php`. The plugin never loads it. Keep it in sync with the config example in `README.md` when you add a setting.

## Install-time constraints

- **Commerce may not be installed yet.** On a fresh `craft install` with project config, Craft installs plugins in `project.yaml` order, and Craft always writes that list alphabetically. So `avatax` installs before `commerce`, and `CommercePlugin::getInstance()` is `null` during Avatax's install. Craft does not order installs by Composer dependencies. Install code must not call Commerce services until Commerce is installed. See #66.
- **Don't duplicate project config items.** Fields, sections and other project-config items must not be created at install time when `project.yaml` already lists the plugin (`Craft::$app->getProjectConfig()->get('plugins.avatax', true) !== null`). Project config brings them along, and creating them as well leaves two fields with the same handle. Database-only data, like the `avatax` tax category, isn't in project config and still needs creating.
- **Install may run without a controller.** `onAfterInstall()` returns early when the env is `test` (#37). Code that redirects at the end of install must not assume `Craft::$app->controller` exists outside a control panel request.

## Architecture

**Wiring (`src/Avatax.php`).** `init()` registers the services as `SalesTaxService` and `LogService` (PascalCase handles, also declared in `composer.json` `extra.components`). It also registers CP URL rules (`avatax/settings`, `avatax/logs`) and four event hooks, which are the whole runtime surface:

| Event | Calls |
|---|---|
| `OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS` | registers `AvataxTaxAdjuster` |
| `Order::EVENT_BEFORE_COMPLETE_ORDER` | `createSalesInvoice()` (commits a SalesInvoice) |
| `Address::EVENT_BEFORE_SAVE` (site requests only) | `validateAddress()` |
| `Payments::EVENT_AFTER_REFUND_TRANSACTION` (status `success`) | `handleRefund()` |

`onAfterInstall()` creates the `avatax` tax category and the `avataxTaxCode` / `avataxCustomerUsageType` fields. It skips this when the env is `test`.

**Tax calculation flow.** `AvataxTaxAdjuster::adjust()` runs on every cart recalculation when the cart has a shipping address and line items. It creates a **new** `SalesTaxService` (not the plugin component) and calls `createSalesOrder()`. It returns one `tax` adjustment holding AvaTax's `totalTax`.

`createSalesOrder()` checks these in order:
1. the cancelable `EVENT_BEFORE_CREATE_SALES_ORDER` event
2. the `enableTaxCalculation` setting
3. the request params `avatax_disable_tax_calculation` / `avatax_force_tax_calculation`

It then builds an `\Avalara\TransactionBuilder` (SalesOrder, not committed). `createSalesInvoice()` does the same with SalesInvoice + `withCommit()`. Both meet in the private `getTotalTax()`, which does most of the work:
- **Line items:** only line items whose tax category handle is `avatax` are sent. The tax code is the product's `avataxTaxCode` field, falling back to `defaultTaxCode`.
- **Shipping:** sent as its own line. `EVENT_APPLY_SHIPPING_CODE` can override its item and tax code.
- **Discounts:** discount adjustments are sent as negative lines with `defaultDiscountCode`, or with the line item's tax code when the discount targets one line item.
- **Exemption:** a logged-in customer's `avataxCustomerUsageType` is sent as the entity/use code.

**Identifiers.**
- The transaction code is `cr_{order.number}`. Refunds use `…-refund` plus a numeric suffix.
- The customer code is the `avataxCustomerCode` field on the User or the Order, falling back to the order email.

The plugin looks up the field handles above (`avataxTaxCode`, `avataxCustomerUsageType`, `avataxCustomerCode`) by exact name, and users depend on them. Do not rename them.

**Caching.** API responses are cached in Craft's data cache. SalesOrder responses are keyed `avatax-{type}-{md5 order signature}` (order number, totals, line items, shipping address). Address validation is keyed `avatax-address-{md5}`. Invoices always call the API again and overwrite the cache entry. If you change which order data is sent to AvaTax, also update `getOrderSignature()`, or stale tax amounts will be served from cache.

**Refunds.** `handleRefund()` compares the rounded refund amount to the payment amount:
- **Full refund:** calls `refundTransaction` with `RefundType::C_FULL`.
- **Partial refund** (needs `enablePartialRefunds`): creates and commits a new ReturnInvoice for the amount. It is not linked to the original AvaTax transaction.

**Settings (`models/Settings.php`).**
- Sandbox and production each have their own credential set. `environment` picks one, and validation requires only the active set.
- Credentials accept `$ENV_VAR` values through `EnvAttributeParserBehavior`. Read them with the getters (`getAccountId()` etc.), which parse env vars, not the raw properties.
- The CertCapture credentials (`certCapture*`) are config-file only and are used by `CertCaptureService`.

**Logging.** `Avatax::info()` / `Avatax::error()` write JSON lines to `storage/logs/avatax.log` through `LogService`. The CP page `avatax/logs` (`UtilityController`) shows and clears them. Full request/response bodies are logged only when the `debug` setting is on. Errors are always logged.

**Controllers.**
- `BaseController` serves the CP settings page.
- `UtilityController` serves the connection test and logs. Both require a logged-in user, but neither checks for admin or any permission.
- `JsonController` allows anonymous access for the front-end AJAX endpoints `actions/avatax/json/validate-address` and `actions/avatax/json/cert-capture-customer`.

The public events and the request params are documented in `README.md` for plugin users. Treat them as public API.

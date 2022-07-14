<?php
/**
 * Avatax plugin for Craft CMS 3.x
 *
 * Calculate and add sales tax to an order's base tax using Avalara's AvaTax service.
 *
 * @link      http://surprisehighway.com
 * @copyright Copyright (c) 2019 Surprise Highway
 */

namespace surprisehighway\avatax;

use surprisehighway\avatax\adjusters\AvataxTaxAdjuster;
use surprisehighway\avatax\models\Settings;
use surprisehighway\avatax\services\SalesTaxService;
use surprisehighway\avatax\services\LogService;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\ModelEvent;
use craft\fields\Dropdown;
use craft\fields\PlainText;
use craft\helpers\UrlHelper;
use craft\models\FieldGroup;
use craft\services\Plugins;
use craft\web\UrlManager;

use craft\commerce\Plugin as CommercePlugin;
use craft\commerce\elements\Order;
use craft\commerce\events\AddressEvent;
use craft\commerce\events\RefundTransactionEvent;
use craft\commerce\models\TaxCategory;
use craft\commerce\models\Transaction;
use craft\elements\Address;
use craft\commerce\services\OrderAdjustments;
use craft\commerce\services\Payments;
use craft\commerce\services\TaxCategories;

use yii\base\Event;

/**
 * Class Avatax
 *
 * @author    Surprise Highway
 * @package   Avatax
 * @since     2.0.0
 *
 * @property  SalesTaxServiceService $salesTaxService
 */
class Avatax extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Avatax
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '2.0.0';


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'SalesTaxService' => SalesTaxService::class,
            'LogService' => LogService::class,
        ]);

        // Register cp urls
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['avatax/settings'] = 'avatax/base/settings';
                $event->rules['avatax/logs'] = 'avatax/utility/logs';
            }
        );

        // Register after plugin install listener
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    $this->onAfterInstall();
                }
            }
        );

        // Register the commerce order tax adjuster
        Event::on(
            OrderAdjustments::class, 
            OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS, 
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = AvataxTaxAdjuster::class;
            }
        );

        // Register order complete listener
        Event::on(
            Order::class, 
            Order::EVENT_BEFORE_COMPLETE_ORDER, 
            function(Event $event) {
                $this->onBeforeOrderComplete($event);
            }
        );
        
        // Register address save event listener
        Event::on(
            Address::class, 
            Address::EVENT_BEFORE_SAVE, 
            function(ModelEvent $event) {
                $this->onBeforeSaveAddress($event);
            }
        );

        // Register order refund listener
        Event::on(
            Payments::class, 
            Payments::EVENT_AFTER_REFUND_TRANSACTION, 
            function(RefundTransactionEvent $event) {
                $this->onRefundTransaction($event);
            }
        );

        // Plugin loaded
        Craft::info(
            Craft::t(
                'avatax',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Raised before a cart is completed and becomes an order.
     * Create a sales invoice in avatax.
     */
    public function onBeforeOrderComplete(Event $event)
    {
        // @var Order $order
        $order = $event->sender;

        $this->SalesTaxService->createSalesInvoice($order);
    }

    /**
     * Raised before address has been saved.
     * Validate an address in avatax.
     */
    public function onBeforeSaveAddress(AddressEvent $event)
    {
        // @var AddressEvent $address
        $address = $event->address;

        if(Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->SalesTaxService->validateAddress($address);
        }
    }

    /**
     * Raised after a transaction was attempted to be refunded.
     * Void a transaction in.
     */
    public function onRefundTransaction(RefundTransactionEvent $event)
    {
        // @var float $amount
        $amount = $event->amount;

        // @var Transaction $transaction
        $transaction = $event->transaction;

        if($transaction->status == 'success')
        {
            $this->SalesTaxService->handleRefund($amount, $transaction);
        }
    }

    /**
     * Raised after the plugin is installed.
     * Create the AvaTax Tax category and product fields.
     */
    public function onAfterInstall()
    {
        if (Craft::$app->config->env === 'test') {
            return;
        }
        
        $commerce = CommercePlugin::getInstance();

        // Create an "avatax" tax category
        $category = $commerce->TaxCategories->getTaxCategoryByHandle('avatax');

        if(!$category)
        {
            $model = new TaxCategory();

            $model->name = 'Avatax';
            $model->handle = 'avatax';
            $model->description = 'Calculate tax rates using Avalara AvaTax';
            $model->default = FALSE;

            if ( $commerce->TaxCategories->saveTaxCategory($model) )
            {
                Craft::info('Avatax tax category created successfully.', 'avatax');
            }
            else
            {
                Craft::warning('Could not save the Avatax tax category.', 'avatax');
            }
        }

        // Create an "avatax field group"
        $group = new FieldGroup();
        $group->name = 'Avatax';

        if( Craft::$app->fields->saveGroup($group) )
        {
            Craft::info('Avatax field group created successfully.', 'avatax');

            // Create avataxTaxCode field
            $field = Craft::$app->fields->createField([
                'groupId'        => $group->id,
                'name'           => 'AvaTax Tax Code',
                'handle'         => 'avataxTaxCode',
                'type'           => PlainText::class,
                'instructions'   => 'Specify an [Avalara Tax Code](https://taxcode.avatax.avalara.com) to use for this product.',
                'settings' => [
                    'placeholder' => '',
                    'multiline'   => false,
                    'initialRows' => '4',
                    'charLimit'   => null,
                ]
            ]);

            if (Craft::$app->fields->saveField($field))
            {
                Craft::info('Avatax Tax Code field created successfully.', 'avatax');
            }
            else
            {
                Craft::warning('Could not save the Avatax Tax Code field.', 'avatax');
            }

            // Create avataxCustomerUsageType field
            $field = Craft::$app->fields->createField([
                'groupId'      => $group->id,
                'name'         => 'AvaTax Customer Usage Type',
                'handle'       => 'avataxCustomerUsageType',
                'type'         => Dropdown::class,
                'instructions' => 'Select an [Entity/Use Code](https://help.avalara.com/000_Avalara_AvaTax/Exemption_Reason_Matrices_for_US_and_Canada) to exempt this customer from tax.',
                'settings' => [
                    'options' => [
                        ['label' => '', 'value' => '', 'default' => ''],
                        ['label' => 'A. Federal government (United States)', 'value' => 'A', 'default' => ''],
                        ['label' => 'B. State government (United States)', 'value' => 'B', 'default' => ''],
                        ['label' => 'C. Tribe / Status Indian / Indian Band (both)', 'value' => 'C', 'default' => ''],
                        ['label' => 'D. Foreign diplomat (both)', 'value' => 'D', 'default' => ''],
                        ['label' => 'E. Charitable or benevolent org (both)', 'value' => 'E', 'default' => ''],
                        ['label' => 'F. Religious or educational org (both)', 'value' => 'F', 'default' => ''],
                        ['label' => 'G. Resale (both)', 'value' => 'G', 'default' => ''],
                        ['label' => 'H. Commercial agricultural production (both)', 'value' => 'H', 'default' => ''],
                        ['label' => 'I. Industrial production / manufacturer (both)', 'value' => 'I', 'default' => ''],
                        ['label' => 'J. Direct pay permit (United States)', 'value' => 'J', 'default' => ''],
                        ['label' => 'K. Direct mail (United States)', 'value' => 'K', 'default' => ''],
                        ['label' => 'L. Other (both)', 'value' => 'L', 'default' => ''],
                        ['label' => 'M. Not Used', 'value' => 'M', 'default' => ''],
                        ['label' => 'N. Local government (United States)', 'value' => 'N', 'default' => ''],
                        ['label' => 'O. Not Used', 'value' => 'O', 'default' => ''],
                        ['label' => 'P. Commercial aquaculture (Canada)', 'value' => 'P', 'default' => ''],
                        ['label' => 'Q. Commercial Fishery (Canada)', 'value' => 'Q', 'default' => ''],
                        ['label' => 'R. Non-resident (Canada)', 'value' => 'R', 'default' => ''],
                    ]
                ]
            ]);

            if (Craft::$app->fields->saveField($field))
            {
                Craft::info('Avatax Customer Usage Type field created successfully.', 'Avatax');
            }
            else
            {
                Craft::warning('Could not save the Avatax Customer Usage Type field.', 'Avatax');
            }
        }
        else
        {
            Craft::warning('Could not save the Avatax field group. ', 'Avatax');
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->controller->redirect(UrlHelper::cpUrl('avatax/settings'));
    }


    // Static Methods
    // =========================================================================

    /**
     * @param string $message
     * @param array $data
     */
    static function info($message, $data = [])
    {
        self::$plugin->LogService->log('info', $message, $data);
    }

    /**
     * @param string $message
     * @param array $data
     */
    static function error($message, $data = [])
    {
        self::$plugin->LogService->log('error', $message, $data);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'avatax/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->controller->redirect(UrlHelper::cpUrl('avatax/settings'));
    }
}

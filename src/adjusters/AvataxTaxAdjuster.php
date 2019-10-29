<?php

namespace surprisehighway\avatax\adjusters;

use surprisehighway\avatax\services\SalesTaxService;

use Craft;
use craft\base\Component;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;

class AvataxTaxAdjuster extends Component implements AdjusterInterface
{
    public function adjust(Order $order): array
    {
        $adjustments = [];

        if ($order->shippingAddress !== null && sizeof($order->getLineItems()) > 0) {
            $taxService = new SalesTaxService();

            $salesTax = $taxService->createSalesOrder($order);

            $adjustment = new OrderAdjustment();

            $adjustment->type = 'tax';
            $adjustment->name = 'Sales Tax';
            $adjustment->description = 'Adds $' . $salesTax . ' of tax to the order';
            $adjustment->sourceSnapshot = ['avatax' => $salesTax];
            $adjustment->amount = +$salesTax;
            $adjustment->setOrder($order);
            $adjustments[] = $adjustment;
        }

        return $adjustments;
    }
}

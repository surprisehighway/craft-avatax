<?php

namespace surprisehighway\avatax\events;

use craft\base\Event;
use craft\commerce\elements\Order;

/**
 * Adjust default shipping taxes applied to an order.
 */
class ApplyShippingCodeEvent extends Event
{
    /**
     * @var Order The order which taxes are being applied to.
     */
    public Order $order;

    /**
     * @var string The shipping item code.
     */
    public string $shippingItemCode = 'FREIGHT';

    /**
     * @var string The shipping tax code.
     */
    public string $shippingTaxCode;
}

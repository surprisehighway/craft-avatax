<?php

namespace surprisehighway\avatax\events;

use craft\events\CancelableEvent;
use craft\commerce\elements\Order;

/**
 * Event triggered before creating an AvaTax sales order calculation.
 * Handlers may set $isValid = false to cancel tax calculation for the given order.
 */
class BeforeCreateSalesOrderEvent extends CancelableEvent
{
    /**
     * The Commerce order instance for which tax calculation is being attempted.
     */
    public Order $order;
}



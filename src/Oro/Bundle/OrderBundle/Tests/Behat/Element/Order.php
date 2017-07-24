<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemsAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\SubtotalAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\Subtotals;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Order extends Element implements LineItemsAwareInterface, SubtotalAwareInterface
{
    /**
     * @param string $subtotalName
     * @return string
     */
    public function getSubtotal($subtotalName)
    {
        /** @var Subtotals $subtotals */
        $subtotals = $this->getElement('Subtotals');

        return $subtotals->getSubtotal($subtotalName);
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('OrderLineItem');
    }
}
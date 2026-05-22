<?php

namespace Ecomail\Ecomail\Plugin\Checkout\Controller\Cart;

use Ecomail\Ecomail\Model\EventManager;

class Delete
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Add constructor.
     * @param EventManager $eventManager
     */
    public function __construct(
        EventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Checkout\Controller\Cart\Delete $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Cart\Delete $subject,
        $result
    ) {
        $this->eventManager->updateCartContent();
        return $result;
    }
}

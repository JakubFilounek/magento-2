<?php

namespace Ecomail\Ecomail\Plugin\Checkout\Controller\Cart;

use Ecomail\Ecomail\Model\EventManager;

class Addgroup
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
     * @param \Magento\Checkout\Controller\Cart\Addgroup $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Cart\Addgroup $subject,
        $result
    ) {
        $this->eventManager->updateCartContent();
        return $result;
    }
}

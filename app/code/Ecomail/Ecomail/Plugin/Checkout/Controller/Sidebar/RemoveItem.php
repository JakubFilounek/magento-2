<?php

namespace Ecomail\Ecomail\Plugin\Checkout\Controller\Sidebar;

use Ecomail\Ecomail\Model\EventManager;

class RemoveItem
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
     * @param \Magento\Checkout\Controller\Sidebar\RemoveItem $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Magento\Checkout\Controller\Sidebar\RemoveItem $subject,
        $result
    ) {
        $this->eventManager->updateCartContent();
        return $result;
    }
}

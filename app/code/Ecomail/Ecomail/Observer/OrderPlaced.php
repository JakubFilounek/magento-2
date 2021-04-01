<?php

namespace Ecomail\Ecomail\Observer;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\SubscriptionManager;
use Ecomail\Ecomail\Model\TransactionManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderPlaced implements ObserverInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * CustomerLogin constructor.
     * @param Data $helper
     * @param TransactionManager $transactionManager
     * @param SubscriptionManager $subscriptionManager
     */
    public function __construct(
        Data $helper,
        TransactionManager $transactionManager,
        SubscriptionManager $subscriptionManager
    ) {
        $this->helper = $helper;
        $this->transactionManager = $transactionManager;
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getOrder();

        if ($this->helper->isAvailable() && $this->helper->sendOrders()) {
            $this->subscriptionManager->subscribeFromOrder($order);
            $this->transactionManager->createTransaction($order);
        }
    }
}

<?php

namespace Ecomail\Ecomail\Observer;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\SubscriptionManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerUpdated implements ObserverInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @param Data $helper
     * @param SubscriptionManager $subscriptionManager
     */
    public function __construct(
        Data $helper,
        SubscriptionManager $subscriptionManager
    ) {
        $this->helper = $helper;
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getCustomer();

        if (!$customer || !$this->helper->isAvailable($customer->getStoreId())) {
            return;
        }

        if (method_exists($customer, 'getDataModel')) {
            $customer = $customer->getDataModel();
        }

        $this->subscriptionManager->updateFromCustomer($customer);
    }
}

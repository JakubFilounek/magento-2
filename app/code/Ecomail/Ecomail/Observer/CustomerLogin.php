<?php

namespace Ecomail\Ecomail\Observer;

use Ecomail\Ecomail\Helper\Data;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerLogin implements ObserverInterface
{

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Data
     */
    private $helper;

    /**
     * CustomerLogin constructor.
     * @param Session $customerSession
     * @param Data $helper
     */
    public function __construct(Session $customerSession, Data $helper)
    {
        $this->customerSession = $customerSession;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var CustomerInterface $customer */
        $customer = $observer->getCustomer();

        if ($this->helper->isAvailable() && $this->helper->isTrackingEnabled()) {
            $this->customerSession->setEcomailEmail($customer->getEmail());
        }
    }
}

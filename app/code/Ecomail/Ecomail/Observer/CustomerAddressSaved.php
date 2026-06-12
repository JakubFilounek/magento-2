<?php

namespace Ecomail\Ecomail\Observer;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\SubscriptionManager;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class CustomerAddressSaved implements ObserverInterface
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
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Data $helper
     * @param SubscriptionManager $subscriptionManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Data $helper,
        SubscriptionManager $subscriptionManager,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->subscriptionManager = $subscriptionManager;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $address = $observer->getCustomerAddress();

        if (!$address || !$address->getCustomerId()) {
            return;
        }

        try {
            $customer = $this->customerRepository->getById((int)$address->getCustomerId());
        } catch (\Exception $e) {
            $this->logger->error('Failed to load customer for Ecomail address sync.', [$e]);

            return;
        }

        $storeId = $customer->getStoreId();

        if (!$this->helper->isAvailable($storeId) || !$this->helper->sendAddress($storeId)) {
            return;
        }

        $this->subscriptionManager->updateFromCustomer($customer);
    }
}

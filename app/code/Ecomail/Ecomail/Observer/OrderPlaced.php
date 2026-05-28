<?php

namespace Ecomail\Ecomail\Observer;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\SubscriptionManager;
use Ecomail\Ecomail\Model\TransactionManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

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
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * CustomerLogin constructor.
     * @param Data $helper
     * @param TransactionManager $transactionManager
     * @param SubscriptionManager $subscriptionManager
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Data $helper,
        TransactionManager $transactionManager,
        SubscriptionManager $subscriptionManager,
        CheckoutSession $checkoutSession
    ) {
        $this->helper = $helper;
        $this->transactionManager = $transactionManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getOrder();

        if ($this->helper->isAvailable($order->getStoreId())) {
            if (
                $this->helper->updateContactsFromOrders($order->getStoreId())
                && !$this->checkoutSession->getData('ecomail_newsletter_opt_out')
            ) {
                $this->subscriptionManager->subscribeFromOrder($order);
            }

            if ($this->helper->sendOrderTransactions($order->getStoreId())) {
                $this->transactionManager->createTransaction($order);
            }

            $this->checkoutSession->unsetData('ecomail_newsletter_opt_out');
        }
    }
}

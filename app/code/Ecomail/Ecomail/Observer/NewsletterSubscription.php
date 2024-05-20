<?php

namespace Ecomail\Ecomail\Observer;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\EventManager;
use Ecomail\Ecomail\Model\QuoteManager;
use Ecomail\Ecomail\Model\SubscriptionManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Newsletter\Model\Subscriber;

class NewsletterSubscription implements ObserverInterface
{

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var QuoteManager
     */
    private $quoteManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * NewsletterSubscription constructor.
     * @param SubscriptionManager $subscriptionManager
     * @param Data $helper
     * @param QuoteManager $quoteManager
     * @param EventManager $eventManager
     */
    public function __construct(
        SubscriptionManager $subscriptionManager,
        Data $helper,
        QuoteManager $quoteManager,
        EventManager $eventManager
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->helper = $helper;
        $this->quoteManager = $quoteManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var Subscriber $subscriber */
        $subscriber = $observer->getSubscriber();

        if ($this->helper->isAvailable() && $subscriber->isStatusChanged()) {
            $newSubscriberStatus = $subscriber->getStatus();

            switch ($newSubscriberStatus) {
                case Subscriber::STATUS_SUBSCRIBED:
                    $this->subscriptionManager->subscribe($subscriber);
                    $this->quoteManager->setQuoteEmail($subscriber->getEmail());
                    $this->eventManager->updateCartContent();
                    return;
                case Subscriber::STATUS_UNSUBSCRIBED:
                    $this->subscriptionManager->unsubscribe($subscriber);
                    return;
                default:
                    return;
            }
        }
    }
}

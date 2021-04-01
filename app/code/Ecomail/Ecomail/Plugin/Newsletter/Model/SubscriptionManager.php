<?php

namespace Ecomail\Ecomail\Plugin\Newsletter\Model;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\EventManager;
use Ecomail\Ecomail\Model\QuoteManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriptionManagerInterface;

class SubscriptionManager
{
    /**
     * @var \Ecomail\Ecomail\Model\SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var QuoteManager
     */
    private $quoteManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * SubscriptionManager constructor.
     * @param \Ecomail\Ecomail\Model\SubscriptionManager $subscriptionManager
     * @param EventManager $eventManager
     * @param QuoteManager $quoteManager
     * @param Data $helper
     */
    public function __construct(
        \Ecomail\Ecomail\Model\SubscriptionManager $subscriptionManager,
        EventManager $eventManager,
        QuoteManager $quoteManager,
        Data $helper
    ) {
        $this->subscriptionManager = $subscriptionManager;
        $this->eventManager = $eventManager;
        $this->quoteManager = $quoteManager;
        $this->helper = $helper;
    }

    /**
     * @param SubscriptionManagerInterface $subject
     * @param Subscriber $result
     * @return Subscriber
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSubscribe(
        SubscriptionManagerInterface $subject,
        Subscriber $result
    ): Subscriber {
        if ($this->helper->isAvailable()) {
            $this->subscriptionManager->subscribe($result);
            $this->quoteManager->setQuoteEmail($result->getEmail());
            $this->eventManager->updateCartContent();
        }

        return $result;
    }

    /**
     * @param SubscriptionManagerInterface $subject
     * @param Subscriber $result
     * @return Subscriber
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSubscribeCustomer(
        SubscriptionManagerInterface $subject,
        Subscriber $result
    ) {
        if ($this->helper->isAvailable()) {
            $this->subscriptionManager->subscribe($result);
            $this->quoteManager->setQuoteEmail($result->getEmail());
            $this->eventManager->updateCartContent();
        }

        return $result;
    }
}

<?php

namespace Ecomail\Ecomail\Plugin\Newsletter\Model;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\EventManager;
use Ecomail\Ecomail\Model\QuoteManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;

class Subscriber
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
     * @param SubscriberModel $subject
     * @param int $result
     * @return int|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSubscribe(
        SubscriberModel $subject,
        $result
    ) {
        if ($this->helper->isAvailable() && $result == SubscriberModel::STATUS_SUBSCRIBED) {
            $this->subscriptionManager->subscribe($subject);
            $this->quoteManager->setQuoteEmail($subject->getEmail());
            $this->eventManager->updateCartContent();
        }

        return $result;
    }

    /**
     * @param SubscriberModel $subject
     * @param SubscriberModel $result
     * @return SubscriberModel
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function afterSubscribeCustomerById(
        SubscriberModel $subject,
        SubscriberModel $result
    ) {
        if ($this->helper->isAvailable() && $result->getStatus() == SubscriberModel::STATUS_SUBSCRIBED) {
            $this->subscriptionManager->subscribe($subject);
            $this->quoteManager->setQuoteEmail($subject->getEmail());
            $this->eventManager->updateCartContent();
        }

        return $result;
    }

    /**
     * @param SubscriberModel $subject
     * @param SubscriberModel $result
     * @return SubscriberModel
     */
    public function afterUnsubscribe(
        SubscriberModel $subject,
        SubscriberModel $result
    ): SubscriberModel {
        if ($this->helper->isAvailable() && $result->getStatus() == SubscriberModel::STATUS_UNSUBSCRIBED) {
            $this->subscriptionManager->unsubscribe($result);
        }

        return $result;
    }

    /**
     * @param SubscriberModel $subject
     * @param SubscriberModel $result
     * @return SubscriberModel
     */
    public function afterUnsubscribeCustomerById(
        SubscriberModel $subject,
        SubscriberModel $result
    ): SubscriberModel {
        if ($this->helper->isAvailable() && $result->getStatus() == SubscriberModel::STATUS_UNSUBSCRIBED) {
            $this->subscriptionManager->unsubscribe($result);
        }

        return $result;
    }
}

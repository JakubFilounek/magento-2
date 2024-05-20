<?php

namespace Ecomail\Ecomail\Model;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class SubscriptionManager
{

    /**
     * @var Api
     */
    private $api;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SubscriberDataMapper
     */
    private $subscriberDataMapper;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * SubscriptionManager constructor.
     * @param Api $api
     * @param LoggerInterface $logger
     * @param SubscriberDataMapper $subscriberDataMapper
     * @param SubscriberFactory $subscriberFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Api $api,
        LoggerInterface $logger,
        SubscriberDataMapper $subscriberDataMapper,
        SubscriberFactory $subscriberFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->subscriberDataMapper = $subscriberDataMapper;
        $this->subscriberFactory = $subscriberFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param Subscriber $subscriber
     */
    public function subscribe(Subscriber $subscriber)
    {
        try {
            $this->api->addSubscriberToList($this->subscriberDataMapper->map($subscriber));
        } catch (Exception $e) {
            $this->logger->error('Failed to add Ecomail subscription.', [$e]);
        }
    }

    /**
     * @param OrderInterface $order
     */
    public function subscribeFromOrder(OrderInterface $order)
    {
        try {
            if (!$this->subscriberExists($order->getCustomerEmail())) {
                $this->createLocalSubscriber($order);
                $this->api->addSubscriberToList($this->subscriberDataMapper->mapFromOrder($order));
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to add Ecomail subscription.', [$e]);
        }
    }

    /**
     * @param Subscriber $subscriber
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        try {
            $this->api->removeSubscriberFromList($subscriber->getEmail());
        } catch (Exception $e) {
            $this->logger->error('Failed to remove Ecomail subscription.', [$e]);
        }
    }

    /**
     * @param string $customerEmail
     * @return bool
     */
    public function subscriberExists(string $customerEmail): bool
    {
        $subscriber = $this->subscriberFactory->create();
        $subscriber->loadByEmail($customerEmail);

        return $subscriber->getId() != null;
    }

    /**
     * @param OrderInterface $order
     */
    private function createLocalSubscriber(OrderInterface $order)
    {
        $subscriber = $this->subscriberFactory->create();
        $subscriber->setSubscriberConfirmCode($subscriber->randomSequence());

        $isConfirmationNeeded = (bool)$this->scopeConfig->getValue(
            Subscriber::XML_PATH_CONFIRMATION_FLAG,
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        $subscriber->setStatus(
            $isConfirmationNeeded ?
                Subscriber::STATUS_NOT_ACTIVE :
                Subscriber::STATUS_SUBSCRIBED
        );

        $subscriber->setSubscriberEmail($order->getCustomerEmail());
        $subscriber->setStoreId($order->getStoreId());

        if ($order->getCustomerId()) {
            $subscriber->setCustomerId($order->getCustomerId());
        }

        try {
            $subscriber->save();
        } catch (Exception $e) {
            $this->logger->error('Failed to create local subscriber.', [$e]);
        }
    }
}

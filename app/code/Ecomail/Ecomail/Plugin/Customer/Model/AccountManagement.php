<?php

namespace Ecomail\Ecomail\Plugin\Customer\Model;

use Ecomail\Ecomail\Model\EventManager;
use Ecomail\Ecomail\Model\QuoteManager;
use Ecomail\Ecomail\Model\SubscriptionManager;

class AccountManagement
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var QuoteManager
     */
    private $quoteManager;

    /**
     * AccountManagement constructor.
     * @param EventManager $eventManager
     * @param SubscriptionManager $subscriptionManager
     * @param QuoteManager $quoteManager
     */
    public function __construct(
        EventManager $eventManager,
        SubscriptionManager $subscriptionManager,
        QuoteManager $quoteManager
    ) {
        $this->eventManager = $eventManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->quoteManager = $quoteManager;
    }

    /**
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param bool $result
     * @param $customerEmail
     * @return bool
     */
    public function afterIsEmailAvailable(
        \Magento\Customer\Model\AccountManagement $subject,
        bool $result,
        string $customerEmail
    ): bool {
        if ($this->subscriptionManager->subscriberExists($customerEmail)) {
            $this->quoteManager->setQuoteEmail($customerEmail);
            $this->eventManager->updateCartContent();
        }

        return $result;
    }
}

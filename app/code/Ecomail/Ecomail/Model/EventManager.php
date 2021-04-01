<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class EventManager
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
     * @var Data
     */
    private $helper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var BasketEventMapper
     */
    private $basketEventMapper;

    /**
     * SubscriptionManager constructor.
     * @param Api $api
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param Session $checkoutSession
     * @param BasketEventMapper $basketEventMapper
     */
    public function __construct(
        Api $api,
        LoggerInterface $logger,
        Data $helper,
        Session $checkoutSession,
        BasketEventMapper $basketEventMapper
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->basketEventMapper = $basketEventMapper;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function updateCartContent()
    {
        if (!$this->helper->sendCartItems() || !$this->checkoutSession->getQuote()->getCustomerEmail()) {
            return;
        }

        try {
            $this->api->updateCart($this->basketEventMapper->map($this->checkoutSession->getQuote()));
        } catch (Exception $e) {
            $this->logger->error('Failed to update Ecomail cart items.', [$e]);
        }
    }
}

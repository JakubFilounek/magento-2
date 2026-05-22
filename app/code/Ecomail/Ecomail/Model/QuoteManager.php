<?php

namespace Ecomail\Ecomail\Model;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class QuoteManager
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * SubscriptionManager constructor.
     * @param Session $checkoutSession
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Session $checkoutSession,
        LoggerInterface $logger,
        CartRepositoryInterface $cartRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param string $email
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setQuoteEmail(string $email)
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if ($quote->getCustomerEmail() === null) {
            try {
                $quote->setCustomerEmail($email);
                $this->cartRepository->save($quote);
            } catch (Exception $e) {
                $this->logger->error('Failed to set quote email', [$e]);
            }
        }
    }
}

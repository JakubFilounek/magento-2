<?php

namespace Ecomail\Ecomail\Model;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class TransactionManager
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
     * @var TransactionMapper
     */
    private $transactionMapper;

    /**
     * SubscriptionManager constructor.
     * @param Api $api
     * @param LoggerInterface $logger
     * @param TransactionMapper $transactionMapper
     */
    public function __construct(
        Api $api,
        LoggerInterface $logger,
        TransactionMapper $transactionMapper
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->transactionMapper = $transactionMapper;
    }

    /**
     * @param OrderInterface $order
     */
    public function createTransaction(OrderInterface $order)
    {
        try {
            $this->api->createTransaction($this->transactionMapper->map($order));
        } catch (Exception $e) {
            $this->logger->error('Failed to create Ecomail transaction.', [$e]);
        }
    }
}

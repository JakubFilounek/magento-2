<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;

class SyncManager
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_RUNNING = 'running';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_FAILED = 'failed';
    private const LOCK_TTL_MINUTES = 60;
    private const MAX_CUSTOMER_BATCH_SIZE = 3000;
    private const MAX_ORDER_BATCH_SIZE = 1000;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var SubscriberDataMapper
     */
    private $subscriberDataMapper;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var TransactionMapper
     */
    private $transactionMapper;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resource
     * @param Data $helper
     * @param Api $api
     * @param SubscriberDataMapper $subscriberDataMapper
     * @param SubscriptionManager $subscriptionManager
     * @param TransactionMapper $transactionMapper
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        Data $helper,
        Api $api,
        SubscriberDataMapper $subscriberDataMapper,
        SubscriptionManager $subscriptionManager,
        TransactionMapper $transactionMapper,
        CustomerCollectionFactory $customerCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->helper = $helper;
        $this->api = $api;
        $this->subscriberDataMapper = $subscriberDataMapper;
        $this->subscriptionManager = $subscriptionManager;
        $this->transactionMapper = $transactionMapper;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * @param int|null $storeId
     * @param int $customerBatchSize
     * @param int $orderBatchSize
     * @param bool $syncCustomers
     * @param bool $syncOrders
     * @param bool|null $updateExisting
     * @param bool|null $includeTags
     * @return array
     */
    public function schedule(
        ?int $storeId,
        int $customerBatchSize = self::MAX_CUSTOMER_BATCH_SIZE,
        int $orderBatchSize = self::MAX_ORDER_BATCH_SIZE,
        bool $syncCustomers = true,
        bool $syncOrders = true,
        ?bool $updateExisting = null,
        ?bool $includeTags = null
    ): array {
        $active = $this->getActive();
        if ($active) {
            return $active;
        }

        $customerBatchSize = max(1, min(self::MAX_CUSTOMER_BATCH_SIZE, $customerBatchSize));
        $orderBatchSize = max(1, min(self::MAX_ORDER_BATCH_SIZE, $orderBatchSize));
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ecomail_sync_state');
        $connection->insert(
            $table,
            [
                'store_id' => $storeId,
                'status' => self::STATUS_PENDING,
                'sync_customers' => $syncCustomers ? 1 : 0,
                'sync_orders' => $syncOrders && $this->helper->sendOrderTransactions($storeId) ? 1 : 0,
                'update_existing' => ($updateExisting ?? $this->helper->syncUpdateExisting($storeId)) ? 1 : 0,
                'include_tags' => ($includeTags ?? $this->helper->syncIncludeTags($storeId)) ? 1 : 0,
                'batch_size' => min($customerBatchSize, $orderBatchSize),
                'customer_batch_size' => $customerBatchSize,
                'order_batch_size' => $orderBatchSize,
                'total_customers' => $syncCustomers ? $this->getCustomerCollection($storeId)->getSize() : 0,
                'total_orders' => $syncOrders && $this->helper->sendOrderTransactions($storeId)
                    ? $this->getOrderCollection($storeId)->getSize()
                    : 0,
                'last_message' => 'Waiting for cron.',
            ]
        );

        return $this->getLatest() ?: [];
    }

    /**
     * Process one batch.
     */
    public function processNextBatch(): void
    {
        $state = $this->acquireState();
        if (!$state) {
            return;
        }

        try {
            if (!$this->helper->isAvailable($state['store_id'])) {
                $this->fail((int)$state['state_id'], 'Ecomail is not enabled or subscriber list is not configured.');
                return;
            }

            if ((int)$state['sync_customers'] && (int)$state['processed_customers'] < (int)$state['total_customers']) {
                $processed = $this->processCustomerBatch($state);
                $this->updateProgress((int)$state['state_id'], 'processed_customers', $processed, 'Customer batch processed.');
                $this->completeIfFinished((int)$state['state_id']);
                return;
            }

            if ((int)$state['sync_orders'] && (int)$state['processed_orders'] < (int)$state['total_orders']) {
                $processed = $this->processOrderBatch($state);
                $this->updateProgress((int)$state['state_id'], 'processed_orders', $processed, 'Order batch processed.');
                $this->completeIfFinished((int)$state['state_id']);
                return;
            }

            $this->complete((int)$state['state_id']);
        } catch (\Exception $e) {
            $this->logger->error('Ecomail initial sync failed.', [$e]);
            $this->fail((int)$state['state_id'], $e->getMessage());
        }
    }

    /**
     * @return array|null
     */
    public function getLatest(): ?array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ecomail_sync_state');
        $row = $connection->fetchRow(
            $connection->select()
                ->from($table)
                ->order('state_id DESC')
                ->limit(1)
        );

        return $row ?: null;
    }

    /**
     * @return array|null
     */
    public function getActive(): ?array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ecomail_sync_state');
        $row = $connection->fetchRow(
            $connection->select()
                ->from($table)
                ->where('status IN (?)', [self::STATUS_PENDING, self::STATUS_RUNNING])
                ->order('state_id DESC')
                ->limit(1)
        );

        return $row ?: null;
    }

    /**
     * @param array $state
     * @return int
     */
    private function processCustomerBatch(array $state): int
    {
        $batchSize = $this->getCustomerBatchSize($state);
        $page = (int)floor((int)$state['processed_customers'] / $batchSize) + 1;
        $collection = $this->getCustomerCollection($state['store_id']);
        $collection->setPageSize($batchSize);
        $collection->setCurPage($page);
        $collection->load();

        $batch = [];

        foreach ($collection as $customer) {
            if (!$customer->getEmail()) {
                continue;
            }

            try {
                $subscriberData = $this->subscriberDataMapper->mapFromCustomer(
                    $customer->getDataModel(),
                    $this->subscriptionManager->subscriberExists($customer->getEmail())
                );
                $batch[] = $subscriberData['subscriber_data'];
            } catch (\Exception $e) {
                $this->logger->error(
                    'Ecomail customer sync mapping failed.',
                    [$e, ['customer_id' => (int)$customer->getId(), 'email' => $this->maskEmail((string)$customer->getEmail())]]
                );
            }
        }

        if ($batch) {
            $this->sendSubscriberBatch(
                $batch,
                (bool)($state['update_existing'] ?? $this->helper->syncUpdateExisting($state['store_id'])),
                (bool)($state['include_tags'] ?? $this->helper->syncIncludeTags($state['store_id']))
            );
        }

        return max(1, $collection->count());
    }

    /**
     * @param array $state
     * @return int
     */
    private function processOrderBatch(array $state): int
    {
        $batchSize = $this->getOrderBatchSize($state);
        $page = (int)floor((int)$state['processed_orders'] / $batchSize) + 1;
        $collection = $this->getOrderCollection($state['store_id']);
        $collection->setPageSize($batchSize);
        $collection->setCurPage($page);
        $collection->load();

        $batch = [];

        foreach ($collection as $order) {
            if (!$order->getCustomerEmail()) {
                continue;
            }

            try {
                $batch[] = $this->transactionMapper->map($order);
            } catch (\Exception $e) {
                $this->logger->error(
                    'Ecomail order sync mapping failed.',
                    [$e, ['order_id' => (string)$order->getIncrementId(), 'email' => $this->maskEmail((string)$order->getCustomerEmail())]]
                );
            }
        }

        if ($batch) {
            $this->sendOrderBatch($batch);
        }

        return max(1, $collection->count());
    }

    /**
     * @return array|null
     */
    private function acquireState(): ?array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ecomail_sync_state');
        $state = $this->getActive();

        if (!$state) {
            return null;
        }

        $where = [
            'state_id = ?' => (int)$state['state_id'],
            'status IN (?)' => [self::STATUS_PENDING, self::STATUS_RUNNING],
            '(locked_at IS NULL OR locked_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ' . self::LOCK_TTL_MINUTES . ' MINUTE) OR status = "pending")',
        ];

        $updated = $connection->update(
            $table,
            [
                'status' => self::STATUS_RUNNING,
                'locked_at' => new Expression('UTC_TIMESTAMP()'),
                'started_at' => $state['started_at'] ?: new Expression('UTC_TIMESTAMP()'),
                'last_message' => 'Running.',
            ],
            $where
        );

        if (!$updated) {
            return null;
        }

        return $connection->fetchRow(
            $connection->select()->from($table)->where('state_id = ?', (int)$state['state_id'])
        ) ?: null;
    }

    /**
     * @param int $stateId
     * @param string $field
     * @param int $increment
     * @param string $message
     */
    private function updateProgress(int $stateId, string $field, int $increment, string $message): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ecomail_sync_state');
        $connection->update(
            $table,
            [
                $field => new Expression($field . ' + ' . max(1, $increment)),
                'locked_at' => null,
                'last_message' => $message,
            ],
            ['state_id = ?' => $stateId]
        );
    }

    /**
     * @param int $stateId
     */
    private function complete(int $stateId): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getTableName('ecomail_sync_state'),
            [
                'status' => self::STATUS_COMPLETED,
                'locked_at' => null,
                'finished_at' => new Expression('UTC_TIMESTAMP()'),
                'last_message' => 'Finished.',
            ],
            ['state_id = ?' => $stateId]
        );
    }

    /**
     * @param int $stateId
     */
    private function completeIfFinished(int $stateId): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ecomail_sync_state');
        $state = $connection->fetchRow(
            $connection->select()->from($table)->where('state_id = ?', $stateId)
        );

        if (!$state) {
            return;
        }

        $customersDone = !(int)$state['sync_customers']
            || (int)$state['processed_customers'] >= (int)$state['total_customers'];
        $ordersDone = !(int)$state['sync_orders']
            || (int)$state['processed_orders'] >= (int)$state['total_orders'];

        if ($customersDone && $ordersDone) {
            $this->complete($stateId);
        }
    }

    /**
     * @param array $batch
     * @param bool $updateExisting
     * @param bool $includeTags
     */
    private function sendSubscriberBatch(array $batch, bool $updateExisting, bool $includeTags): void
    {
        try {
            $this->api->bulkSubscribeToList($batch, $updateExisting, $includeTags);
        } catch (\Exception $e) {
            if (count($batch) <= 1) {
                $this->logger->error('Ecomail subscriber sync item failed.', [$e, $this->getSubscriberLogContext(reset($batch))]);
                return;
            }

            foreach (array_chunk($batch, (int)ceil(count($batch) / 2)) as $chunk) {
                $this->sendSubscriberBatch($chunk, $updateExisting, $includeTags);
            }
        }
    }

    /**
     * @param array $batch
     */
    private function sendOrderBatch(array $batch): void
    {
        try {
            $this->api->bulkOrders($batch);
        } catch (\Exception $e) {
            if (count($batch) <= 1) {
                $order = reset($batch);
                try {
                    $this->api->createTransaction($order);
                } catch (\Exception $createException) {
                    try {
                        $this->api->updateTransaction($order);
                    } catch (\Exception $updateException) {
                        $this->logger->error(
                            'Ecomail order sync item failed.',
                            [$e, $createException, $updateException, $this->getOrderLogContext($order)]
                        );
                    }
                }
                return;
            }

            foreach (array_chunk($batch, (int)ceil(count($batch) / 2)) as $chunk) {
                $this->sendOrderBatch($chunk);
            }
        }
    }

    /**
     * @param int $stateId
     * @param string $message
     */
    private function fail(int $stateId, string $message): void
    {
        $this->resource->getConnection()->update(
            $this->resource->getTableName('ecomail_sync_state'),
            [
                'status' => self::STATUS_FAILED,
                'locked_at' => null,
                'finished_at' => new Expression('UTC_TIMESTAMP()'),
                'last_message' => substr($message, 0, 255),
            ],
            ['state_id = ?' => $stateId]
        );
    }

    /**
     * @param int|string|null $storeId
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    private function getCustomerCollection($storeId)
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect('*');

        if ($storeId !== null && $storeId !== '') {
            $collection->addFieldToFilter('store_id', (int)$storeId);
        }

        return $collection;
    }

    /**
     * @param int|string|null $storeId
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    private function getOrderCollection($storeId)
    {
        $collection = $this->orderCollectionFactory->create();

        if ($storeId !== null && $storeId !== '') {
            $collection->addFieldToFilter('store_id', (int)$storeId);
        }

        return $collection;
    }

    /**
     * @param array $state
     * @return int
     */
    private function getCustomerBatchSize(array $state): int
    {
        return max(1, min(self::MAX_CUSTOMER_BATCH_SIZE, (int)($state['customer_batch_size'] ?? $state['batch_size'] ?? 1)));
    }

    /**
     * @param array $state
     * @return int
     */
    private function getOrderBatchSize(array $state): int
    {
        return max(1, min(self::MAX_ORDER_BATCH_SIZE, (int)($state['order_batch_size'] ?? $state['batch_size'] ?? 1)));
    }

    /**
     * @param mixed $subscriber
     * @return array
     */
    private function getSubscriberLogContext($subscriber): array
    {
        $email = is_array($subscriber) ? (string)($subscriber['email'] ?? '') : '';

        return ['email' => $this->maskEmail($email)];
    }

    /**
     * @param mixed $order
     * @return array
     */
    private function getOrderLogContext($order): array
    {
        $transaction = is_array($order) ? ($order['transaction'] ?? []) : [];

        return [
            'order_id' => (string)($transaction['order_id'] ?? ''),
            'email' => $this->maskEmail((string)($transaction['email'] ?? '')),
        ];
    }

    /**
     * @param string $email
     * @return string
     */
    private function maskEmail(string $email): string
    {
        if (strpos($email, '@') === false) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);

        return substr($local, 0, 1) . '***@' . $domain;
    }
}

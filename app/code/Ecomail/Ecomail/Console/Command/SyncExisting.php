<?php

namespace Ecomail\Ecomail\Console\Command;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\Api;
use Ecomail\Ecomail\Model\SubscriberDataMapper;
use Ecomail\Ecomail\Model\SubscriptionManager;
use Ecomail\Ecomail\Model\SyncManager;
use Ecomail\Ecomail\Model\TransactionMapper;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncExisting extends Command
{
    private const OPTION_STORE_ID = 'store-id';
    private const OPTION_BATCH_SIZE = 'batch-size';
    private const MAX_BATCH_SIZE = 1000;
    private const OPTION_CUSTOMERS_ONLY = 'customers-only';
    private const OPTION_ORDERS_ONLY = 'orders-only';

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
     * @var TransactionMapper
     */
    private $transactionMapper;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    /**
     * @var CustomerCollectionFactory
     */
    private $customerCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @param Data $helper
     * @param Api $api
     * @param SubscriberDataMapper $subscriberDataMapper
     * @param TransactionMapper $transactionMapper
     * @param SubscriptionManager $subscriptionManager
     * @param SyncManager $syncManager
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param string|null $name
     */
    public function __construct(
        Data $helper,
        Api $api,
        SubscriberDataMapper $subscriberDataMapper,
        TransactionMapper $transactionMapper,
        SubscriptionManager $subscriptionManager,
        SyncManager $syncManager,
        CustomerCollectionFactory $customerCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->helper = $helper;
        $this->api = $api;
        $this->subscriberDataMapper = $subscriberDataMapper;
        $this->transactionMapper = $transactionMapper;
        $this->subscriptionManager = $subscriptionManager;
        $this->syncManager = $syncManager;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('ecomail:sync:existing')
            ->setDescription('Synchronise existing Magento customers and orders to Ecomail.')
            ->addOption(self::OPTION_STORE_ID, null, InputOption::VALUE_OPTIONAL, 'Limit sync to a store ID.')
            ->addOption(self::OPTION_BATCH_SIZE, null, InputOption::VALUE_OPTIONAL, 'Batch size.', 100)
            ->addOption(self::OPTION_CUSTOMERS_ONLY, null, InputOption::VALUE_NONE, 'Synchronise customers only.')
            ->addOption(self::OPTION_ORDERS_ONLY, null, InputOption::VALUE_NONE, 'Synchronise orders only.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storeId = $input->getOption(self::OPTION_STORE_ID);
        $batchSize = max(1, min(self::MAX_BATCH_SIZE, (int)$input->getOption(self::OPTION_BATCH_SIZE)));

        if (!$this->helper->isAvailable($storeId)) {
            $output->writeln('<error>Ecomail is not enabled or subscriber list is not configured for this scope.</error>');

            return 1;
        }

        if (!$this->helper->syncExisting($storeId)) {
            $output->writeln('<error>Initial sync is not allowed in Ecomail configuration. Enable "Allow initial sync" first.</error>');

            return 1;
        }

        $state = $this->syncManager->schedule(
            $storeId !== null && $storeId !== '' ? (int)$storeId : null,
            $batchSize,
            !$input->getOption(self::OPTION_ORDERS_ONLY),
            !$input->getOption(self::OPTION_CUSTOMERS_ONLY) && $this->helper->sendOrderTransactions($storeId)
        );

        $output->writeln(sprintf(
            '<info>Ecomail initial sync job is %s. Cron will process it in batches.</info>',
            $state['status'] ?? 'scheduled'
        ));

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param int|string|null $storeId
     * @param int $batchSize
     */
    private function syncCustomers(OutputInterface $output, $storeId, int $batchSize): void
    {
        $page = 1;
        $sent = 0;

        do {
            $collection = $this->customerCollectionFactory->create();
            $collection->addAttributeToSelect('*');

            if ($storeId !== null && $storeId !== '') {
                $collection->addFieldToFilter('store_id', (int)$storeId);
            }

            $collection->setPageSize($batchSize);
            $collection->setCurPage($page);
            $collection->load();

            $batch = [];

            foreach ($collection as $customer) {
                if (!$customer->getEmail()) {
                    continue;
                }

                $subscriberData = $this->subscriberDataMapper->mapFromCustomer(
                    $customer->getDataModel(),
                    $this->subscriptionManager->subscriberExists($customer->getEmail())
                );
                $batch[] = $subscriberData['subscriber_data'];
            }

            if ($batch) {
                $this->api->bulkSubscribeToList($batch);
                $sent += count($batch);
                $output->writeln(sprintf('Synced %d customers...', $sent));
            }

            $page++;
        } while ($collection->getCurPage() < $collection->getLastPageNumber());
    }

    /**
     * @param OutputInterface $output
     * @param int|string|null $storeId
     * @param int $batchSize
     */
    private function syncOrders(OutputInterface $output, $storeId, int $batchSize): void
    {
        $page = 1;
        $sent = 0;

        do {
            $collection = $this->orderCollectionFactory->create();

            if ($storeId !== null && $storeId !== '') {
                $collection->addFieldToFilter('store_id', (int)$storeId);
            }

            $collection->setPageSize($batchSize);
            $collection->setCurPage($page);
            $collection->load();

            $batch = [];

            foreach ($collection as $order) {
                if (!$order->getCustomerEmail()) {
                    continue;
                }

                $batch[] = $this->transactionMapper->map($order);
            }

            if ($batch) {
                $this->api->bulkOrders($batch);
                $sent += count($batch);
                $output->writeln(sprintf('Synced %d orders...', $sent));
            }

            $page++;
        } while ($collection->getCurPage() < $collection->getLastPageNumber());
    }
}

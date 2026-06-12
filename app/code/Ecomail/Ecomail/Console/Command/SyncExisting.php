<?php

namespace Ecomail\Ecomail\Console\Command;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\SyncManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncExisting extends Command
{
    private const OPTION_STORE_ID = 'store-id';
    private const OPTION_BATCH_SIZE = 'batch-size';
    private const OPTION_CUSTOMER_BATCH_SIZE = 'customer-batch-size';
    private const OPTION_ORDER_BATCH_SIZE = 'order-batch-size';
    private const MAX_CUSTOMER_BATCH_SIZE = 3000;
    private const MAX_ORDER_BATCH_SIZE = 1000;
    private const OPTION_CUSTOMERS_ONLY = 'customers-only';
    private const OPTION_ORDERS_ONLY = 'orders-only';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @param Data $helper
     * @param SyncManager $syncManager
     * @param string|null $name
     */
    public function __construct(
        Data $helper,
        SyncManager $syncManager,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->helper = $helper;
        $this->syncManager = $syncManager;
    }

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this->setName('ecomail:sync:existing')
            ->setDescription('Synchronise existing Magento customers and orders to Ecomail.')
            ->addOption(self::OPTION_STORE_ID, null, InputOption::VALUE_OPTIONAL, 'Limit sync to a store ID.')
            ->addOption(
                self::OPTION_BATCH_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Legacy batch size for both customers and orders.'
            )
            ->addOption(
                self::OPTION_CUSTOMER_BATCH_SIZE,
                null,
                InputOption::VALUE_OPTIONAL,
                'Customer batch size.',
                3000
            )
            ->addOption(self::OPTION_ORDER_BATCH_SIZE, null, InputOption::VALUE_OPTIONAL, 'Order batch size.', 1000)
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
        $legacyBatchSize = $input->getOption(self::OPTION_BATCH_SIZE);
        $customerBatchSize = $legacyBatchSize !== null
            ? (int)$legacyBatchSize
            : (int)$input->getOption(self::OPTION_CUSTOMER_BATCH_SIZE);
        $orderBatchSize = $legacyBatchSize !== null
            ? (int)$legacyBatchSize
            : (int)$input->getOption(self::OPTION_ORDER_BATCH_SIZE);

        $customerBatchSize = max(1, min(self::MAX_CUSTOMER_BATCH_SIZE, $customerBatchSize));
        $orderBatchSize = max(1, min(self::MAX_ORDER_BATCH_SIZE, $orderBatchSize));

        if (!$this->helper->isAvailable($storeId)) {
            $output->writeln(
                '<error>Ecomail is not enabled or subscriber list is not configured for this scope.</error>'
            );

            return 1;
        }

        if (!$this->helper->syncExisting($storeId)) {
            $output->writeln(
                '<error>Initial sync is not allowed in Ecomail configuration.'
                . ' Enable "Allow initial sync" first.</error>'
            );

            return 1;
        }

        $state = $this->syncManager->schedule(
            $storeId !== null && $storeId !== '' ? (int)$storeId : null,
            $customerBatchSize,
            $orderBatchSize,
            !$input->getOption(self::OPTION_ORDERS_ONLY),
            !$input->getOption(self::OPTION_CUSTOMERS_ONLY) && $this->helper->sendOrderTransactions($storeId),
            $this->helper->syncUpdateExisting($storeId),
            $this->helper->syncIncludeTags($storeId)
        );

        $output->writeln(sprintf(
            '<info>Ecomail initial sync job is %s. Cron will process it in batches.</info>',
            $state['status'] ?? 'scheduled'
        ));

        return 0;
    }
}

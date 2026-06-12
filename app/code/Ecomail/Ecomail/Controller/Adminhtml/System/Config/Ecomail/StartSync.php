<?php

namespace Ecomail\Ecomail\Controller\Adminhtml\System\Config\Ecomail;

use Ecomail\Ecomail\Model\SyncManager;
use Ecomail\Ecomail\Helper\Data;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class StartSync extends Action
{
    public const ADMIN_RESOURCE = 'Ecomail_Ecomail::ecomail_configuration';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var SyncManager
     */
    private $syncManager;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SyncManager $syncManager
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SyncManager $syncManager,
        Data $helper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->syncManager = $syncManager;
        $this->helper = $helper;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $storeId = $storeId !== '' && $storeId !== null ? (int)$storeId : null;
        $state = $this->syncManager->schedule(
            $storeId,
            $this->getIntegerParam('customer_batch_size', $this->helper->getSyncCustomerBatchSize($storeId)),
            $this->getIntegerParam('order_batch_size', $this->helper->getSyncOrderBatchSize($storeId)),
            (bool)$this->getRequest()->getParam('sync_customers', true),
            (bool)$this->getRequest()->getParam('sync_orders', true),
            $this->getBooleanParam('update_existing', $this->helper->syncUpdateExisting($storeId)),
            $this->getBooleanParam('include_tags', $this->helper->syncIncludeTags($storeId))
        );

        return $this->resultJsonFactory->create()->setData(['state' => $state]);
    }

    /**
     * @param string $name
     * @param bool $default
     * @return bool
     */
    private function getBooleanParam(string $name, bool $default): bool
    {
        $value = $this->getRequest()->getParam($name, null);

        if ($value === null || $value === '') {
            return $default;
        }

        return (bool)(int)$value;
    }

    /**
     * @param string $name
     * @param int $default
     * @return int
     */
    private function getIntegerParam(string $name, int $default): int
    {
        $value = $this->getRequest()->getParam($name, null);

        if ($value === null || $value === '') {
            return $default;
        }

        return (int)$value;
    }
}

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
    const ADMIN_RESOURCE = 'Ecomail_Ecomail::ecomail_configuration';

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
            $this->helper->getSyncCustomerBatchSize($storeId),
            $this->helper->getSyncOrderBatchSize($storeId),
            (bool)$this->getRequest()->getParam('sync_customers', true),
            (bool)$this->getRequest()->getParam('sync_orders', true)
        );

        return $this->resultJsonFactory->create()->setData(['state' => $state]);
    }
}

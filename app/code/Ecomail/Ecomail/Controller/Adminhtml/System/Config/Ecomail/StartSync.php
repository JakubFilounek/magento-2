<?php

namespace Ecomail\Ecomail\Controller\Adminhtml\System\Config\Ecomail;

use Ecomail\Ecomail\Model\SyncManager;
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
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SyncManager $syncManager
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SyncManager $syncManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->syncManager = $syncManager;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $storeId = $this->getRequest()->getParam('store_id');
        $batchSize = (int)$this->getRequest()->getParam('batch_size', 100);
        $state = $this->syncManager->schedule(
            $storeId !== '' && $storeId !== null ? (int)$storeId : null,
            max(1, $batchSize),
            (bool)$this->getRequest()->getParam('sync_customers', true),
            (bool)$this->getRequest()->getParam('sync_orders', true)
        );

        return $this->resultJsonFactory->create()->setData(['state' => $state]);
    }
}

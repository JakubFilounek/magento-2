<?php

namespace Ecomail\Ecomail\Controller\Adminhtml\System\Config\Ecomail;

use Ecomail\Ecomail\Model\SyncManager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

class SyncStatus extends Action
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
        return $this->resultJsonFactory->create()->setData(['state' => $this->syncManager->getLatest()]);
    }
}

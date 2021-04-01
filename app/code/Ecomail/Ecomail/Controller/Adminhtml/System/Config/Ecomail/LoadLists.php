<?php

namespace Ecomail\Ecomail\Controller\Adminhtml\System\Config\Ecomail;

use Ecomail\Ecomail\Model\Api;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class LoadLists extends Action
{
    const ADMIN_RESOURCE = 'Ecomail_Ecomail::ecomail_configuration';

    /**
     * @var Api
     */
    private $api;

    public function __construct(
        Context $context,
        Api $api
    ) {
        parent::__construct($context);
        $this->api = $api;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $apiKey = $this->getRequest()->getParam('api_key');

        try {
            $subscriberLists = $this->api->getSubscriberLists($apiKey);
        } catch (Exception $e) {
            return $result->setData([]);
        }

        $response = [];

        foreach ($subscriberLists as $list) {
            $response[] = [
                'label' => $list['name'],
                'value' => $list['id'],
            ];
        }

        return $result->setData($response);
    }
}

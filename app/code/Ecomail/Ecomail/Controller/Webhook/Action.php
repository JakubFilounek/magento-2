<?php
namespace Ecomail\Ecomail\Controller\Webhook;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber as SubscriberResource;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Exception\NotFoundException;
use Ecomail\Ecomail\Helper\Data;
use Psr\Log\LoggerInterface;

class Action implements  HttpPostActionInterface
{
    const REQUEST_SUBSCRIBE = 'SUBSCRIBED';
    const REQUEST_UNSUBSCRIBE = 'UNSUBSCRIBED';

    /** @var Data */
    private $helper;

    /** @var File */
    private File $file;

    /** @var LoggerInterface */
    private $logger;

    /** @var Json */
    private $json;

    /** @var ResultFactory */
    private $resultFactory;

    /** @var SubscriberFactory */
    private $subscriberFactory;

    /** @var SubscriberResource */
    private $subscriberResource;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var RequestInterface */
    private $httpRequest;

    /**
     * @param Data $helper
     * @param Json $json
     * @param File $file
     * @param LoggerInterface $logger
     * @param RequestInterface $httpRequest
     * @param ResultFactory $resultFactory
     * @param StoreManagerInterface $storeManager
     * @param SubscriberFactory $subscriberFactory
     * @param SubscriberResource $subscriberResource
     */
    public function __construct(
        Data $helper,
        Json $json,
        File $file,
        LoggerInterface $logger,
        RequestInterface $httpRequest,
        ResultFactory $resultFactory,
        StoreManagerInterface $storeManager,
        SubscriberFactory $subscriberFactory,
        SubscriberResource $subscriberResource
    ) {
        $this->helper = $helper;
        $this->json = $json;
        $this->file = $file;
        $this->logger = $logger;
        $this->httpRequest = $httpRequest;
        $this->resultFactory = $resultFactory;
        $this->storeManager = $storeManager;
        $this->subscriberFactory = $subscriberFactory;
        $this->subscriberResource = $subscriberResource;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        try {
            $request = $this->readRequest();
            if ($request['status'] == self::REQUEST_UNSUBSCRIBE) {
                $this->unsubscribe($request['email']);
            } elseif ($request['status'] == self::REQUEST_SUBSCRIBE) {
                $this->subscribe($request['email']);
            }
            $resultRaw->setContents('SUCCESS');
        } catch (\Exception $e) {
            $this->logger->error('Ecomail webhook error: ' . $e->getMessage());
            $resultRaw->setContents('ERROR')->setHttpResponseCode(400);
        }

        return $resultRaw;
    }

    /**
     * @param string $email
     * @return void
     * @throws AlreadyExistsException
     */
    private function unsubscribe(string $email)
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadBySubscriberEmail($email, $this->storeManager->getDefaultStoreView()->getWebsiteId());

        if (!$subscriber->getId()
            || $subscriber->getSubscriberStatus() == Subscriber::STATUS_UNSUBSCRIBED
        ) {
            return;
        }

        $subscriber->setSubscriberStatus(Subscriber::STATUS_UNSUBSCRIBED);
        $this->subscriberResource->save($subscriber);
    }

    /**
     * @param string $email
     * @return void
     * @throws AlreadyExistsException
     */
    private function subscribe(string $email)
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadBySubscriberEmail($email, $this->storeManager->getDefaultStoreView()->getWebsiteId());

        if (!$subscriber->getId()
            || $subscriber->getSubscriberStatus() == Subscriber::STATUS_SUBSCRIBED
        ) {
            return;
        }

        $subscriber->setSubscriberStatus(Subscriber::STATUS_SUBSCRIBED);
        $this->subscriberResource->save($subscriber);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function readRequest()
    {
        $params = $this->httpRequest->getParams();
        $keys = array_keys($params);
        if (array_shift($keys) != $this->helper->getWebhookHash()) {
            throw new NotFoundException(__('Invalid Webhook key.'));
        }

        if ($content = $this->file->fileGetContents('php://input')) {
            try {
                $request = $this->json->unserialize($content);
            } catch (\InvalidArgumentException $e) {
                throw new LocalizedException(__('Invalid request data, JSON unserialize error.'));
            }

            if (isset($request['payload'])) {
                return $request['payload'];
            }
        }
        throw new LocalizedException(__('Invalid request data.'));
    }
}

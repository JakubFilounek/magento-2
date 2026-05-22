<?php

namespace Ecomail\Ecomail\Controller\Webhook;

use Ecomail\Ecomail\Helper\Data;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
use Magento\Newsletter\Model\SubscriberFactory;
use Psr\Log\LoggerInterface;

class Subscriber implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param SubscriberFactory $subscriberFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        Data $helper,
        SubscriberFactory $subscriberFactory,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->subscriberFactory = $subscriberFactory;
        $this->logger = $logger;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();
        $storeId = $this->request->getParam('store');
        $expectedToken = (string)$this->helper->getWebhookToken($storeId);
        $providedToken = (string)$this->request->getParam('token');

        if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            return $result->setHttpResponseCode(403)->setData(['error' => 'Invalid token']);
        }

        $payload = json_decode((string)$this->request->getContent(), true);
        $email = $payload['payload']['email'] ?? null;
        $status = $payload['payload']['status'] ?? null;

        if (!$email || !$status) {
            return $result->setHttpResponseCode(400)->setData(['error' => 'Missing email or status']);
        }

        try {
            $subscriber = $this->subscriberFactory->create();
            $subscriber->loadByEmail($email);

            if (!$subscriber->getId()) {
                return $result->setData(['updated' => false, 'message' => 'Subscriber not found']);
            }

            $subscriber->setStatus(
                strtoupper((string)$status) === 'SUBSCRIBED'
                    ? SubscriberModel::STATUS_SUBSCRIBED
                    : SubscriberModel::STATUS_UNSUBSCRIBED
            );
            $subscriber->save();
        } catch (\Exception $e) {
            $this->logger->error('Failed to process Ecomail webhook.', [$e]);

            return $result->setHttpResponseCode(500)->setData(['error' => 'Unable to update subscriber']);
        }

        return $result->setData(['updated' => true]);
    }
}

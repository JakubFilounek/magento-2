<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory as ClientFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Zend_Http_Client_Exception;
use Zend_Http_Response;

class Api
{
    const CLIENT_TIMEOUT = 5;
    const API_URL = 'https://api2.ecomailapp.cz/';
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const UNKNOWN_ERROR_MESSAGE = 'UNKNOWN ERROR';

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Api constructor.
     * @param ClientFactory $clientFactory
     * @param JsonSerializer $jsonSerializer
     * @param Data $helper
     */
    public function __construct(
        ClientFactory $clientFactory,
        JsonSerializer $jsonSerializer,
        Data $helper
    ) {

        $this->clientFactory = $clientFactory;
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
    }

    /**
     * @param array $subscriberData
     * @return array
     * @throws IntegrationException
     * @throws Zend_Http_Client_Exception
     */
    public function addSubscriberToList(array $subscriberData): array
    {
        $client = $this->getClient();
        $client->setUri($this->buildListUrl('subscribe'));
        $client->setParameterPost($subscriberData);
        $response = $client->request(ZendClient::POST);

        return $this->processResponse($response);
    }

    /**
     * @param string $email
     * @return array
     * @throws IntegrationException
     * @throws Zend_Http_Client_Exception
     */
    public function removeSubscriberFromList(string $email): array
    {
        $client = $this->getClient();

        $client->setUri($this->buildListUrl('unsubscribe'));
        $client->setParameterPost(['email' => $email]);
        $response = $client->request(ZendClient::DELETE);

        return $this->processResponse($response);
    }

    /**
     * @param null $apiKey
     * @return array
     * @throws IntegrationException
     * @throws Zend_Http_Client_Exception
     */
    public function getSubscriberLists($apiKey = null): array
    {
        $client = $this->getClient($apiKey);
        $client->setUri(self::API_URL . 'lists');
        $response = $client->request(ZendClient::GET);

        return $this->processResponse($response);
    }

    /**
     * @param array $data
     * @return array
     * @throws IntegrationException
     * @throws Zend_Http_Client_Exception
     */
    public function createTransaction(array $data): array
    {
        $client = $this->getClient();
        $client->setUri(self::API_URL . 'tracker/transaction');
        $client->setParameterPost($data);
        $response = $client->request(ZendClient::POST);

        return $this->processResponse($response);
    }

    /**
     * @param $data
     * @return array
     * @throws IntegrationException
     * @throws Zend_Http_Client_Exception
     */
    public function updateCart($data): array
    {
        $client = $this->getClient();
        $client->setUri(self::API_URL . 'tracker/events');
        $client->setParameterPost($data);
        $response = $client->request(ZendClient::POST);

        return $this->processResponse($response);
    }

    /**
     * @param Zend_Http_Response $response
     * @return array
     * @throws IntegrationException
     */
    private function processResponse(Zend_Http_Response $response): array
    {
        $status = $response->getStatus();
        $response = $this->jsonSerializer->unserialize($response->getBody());

        if ($status !== self::STATUS_OK && $status !== self::STATUS_CREATED) {
            throw new IntegrationException(__($this->getErrorMessage($response)));
        }

        return $response;
    }

    /**
     * @param null $apiKey
     * @return ZendClient
     * @throws Zend_Http_Client_Exception
     */
    private function getClient($apiKey = null): ZendClient
    {
        if ($apiKey === null) {
            $apiKey = $this->helper->getApiKey();
        }

        /** @var ZendClient $client */
        $client = $this->clientFactory->create();

        $client->setConfig(['timeout' => self::CLIENT_TIMEOUT]);
        $client->setHeaders([
            'Content-Type' => 'application/json',
            'Key' => $apiKey
        ]);

        return $client;
    }

    /**
     * @param string $path
     * @return string
     */
    private function buildListUrl(string $path): string
    {
        return self::API_URL . 'lists' . DIRECTORY_SEPARATOR . $this->helper->getSubscriberList() .
            DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @param array $response
     * @return string
     */
    private function getErrorMessage(array $response): string
    {
        if (isset($response['message'])) {
            return $response['message'];
        }

        if (isset($response['errors'])) {
            $messages = [];

            foreach ($response['errors'] as $errorType) {
                foreach ($errorType as $error) {
                    $messages[] = $error;
                }
            }

            return __('Ecomail api error: ') . implode(', ', $messages);
        }

        return self::UNKNOWN_ERROR_MESSAGE;
    }
}

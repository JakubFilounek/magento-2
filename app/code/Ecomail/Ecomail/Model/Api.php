<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Ecomail\Ecomail\Model\HTTP\CurlFactory;
use Ecomail\Ecomail\Model\HTTP\Curl;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class Api
{
    const CLIENT_TIMEOUT = 5;
    const API_URL = 'https://api2.ecomailapp.cz/';
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const UNKNOWN_ERROR_MESSAGE = 'Unknown Error';

    /**
     * @var CurlFactory
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
     * @param CurlFactory $clientFactory
     * @param JsonSerializer $jsonSerializer
     * @param Data $helper
     */
    public function __construct(
        CurlFactory $clientFactory,
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
     */
    public function addSubscriberToList(array $subscriberData): array
    {
        $client = $this->getClient();
        $client->post($this->buildListUrl('subscribe'), $this->jsonSerializer->serialize($subscriberData));

        return $this->processResponse($client);
    }

    /**
     * @param string $email
     * @return array
     * @throws IntegrationException
     */
    public function removeSubscriberFromList(string $email): array
    {
        $client = $this->getClient();
        $client->delete($this->buildListUrl('unsubscribe'), $this->jsonSerializer->serialize(['email' => $email]));

        return $this->processResponse($client);
    }

    /**
     * @param null $apiKey
     * @return array
     * @throws IntegrationException
     */
    public function getSubscriberLists($apiKey = null): array
    {
        $client = $this->getClient($apiKey);
        $client->get(self::API_URL . 'lists');

        return $this->processResponse($client);
    }

    /**
     * @param array $data
     * @return array
     * @throws IntegrationException
     */
    public function createTransaction(array $data): array
    {
        $client = $this->getClient();
        $client->post(self::API_URL . 'tracker/transaction', $this->jsonSerializer->serialize($data));

        return $this->processResponse($client);
    }

    /**
     * @param $data
     * @return array
     * @throws IntegrationException
     */
    public function updateCart($data): array
    {
        $client = $this->getClient();
        $client->post(self::API_URL . 'tracker/events', $this->jsonSerializer->serialize($data));

        return $this->processResponse($client);
    }

    /**
     * @param Curl $client
     * @return array
     * @throws IntegrationException
     */
    private function processResponse(Curl $client): array
    {
        $status = $client->getStatus();
        $body = $client->getBody();

        try {
            $response = $this->jsonSerializer->unserialize($body);
        } catch (\InvalidArgumentException $e) {
            throw new IntegrationException(__('Ecomail api error: %1', $body));
        }

        if ($status !== self::STATUS_OK && $status !== self::STATUS_CREATED) {
            throw new IntegrationException($this->getErrorMessage($response));
        }

        return $response;
    }

    /**
     * @param null $apiKey
     * @return Curl
     */
    private function getClient($apiKey = null): Curl
    {
        if ($apiKey === null) {
            $apiKey = $this->helper->getApiKey();
        }

        /** @var Curl $client */
        $client = $this->clientFactory->create();
        $client->setOptions([
            CURLOPT_TIMEOUT => self::CLIENT_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::CLIENT_TIMEOUT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2
        ]);
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
     * @return Phrase
     */
    private function getErrorMessage(array $response): Phrase
    {
        if (isset($response['message'])) {
            return __($response['message']);
        }

        if (isset($response['errors'])) {
            $messages = [];

            foreach ($response['errors'] as $errorType) {
                foreach ($errorType as $error) {
                    $messages[] = $error;
                }
            }

            return __('Ecomail api error: %1', implode(', ', $messages));
        }

        return __(self::UNKNOWN_ERROR_MESSAGE);
    }
}

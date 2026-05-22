<?php

namespace Ecomail\Ecomail\Model;

use Ecomail\Ecomail\Helper\Data;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class Api
{
    const CLIENT_TIMEOUT = 5;
    const API_URL = 'https://api2.ecomailapp.cz/';
    const API_NEW_URL = 'https://apinew.ecomailapp.cz/';
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const UNKNOWN_ERROR_MESSAGE = 'UNKNOWN ERROR';
    const MAX_BULK_SUBSCRIBERS = 3000;
    const MAX_BULK_TRANSACTIONS = 1000;

    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ApiLog|null
     */
    private $apiLog;

    /**
     * @var array
     */
    private static $subscriberListsCache = [];

    /**
     * Api constructor.
     * @param JsonSerializer $jsonSerializer
     * @param Data $helper
     * @param ApiLog|null $apiLog
     */
    public function __construct(
        JsonSerializer $jsonSerializer,
        Data $helper,
        ?ApiLog $apiLog = null
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->apiLog = $apiLog;
    }

    /**
     * @param array $subscriberData
     * @return array
     * @throws IntegrationException
     */
    public function addSubscriberToList(array $subscriberData): array
    {
        return $this->request('POST', $this->buildListUrl('subscribe'), $subscriberData);
    }

    /**
     * @param string $email
     * @return array
     * @throws IntegrationException
     */
    public function removeSubscriberFromList(string $email): array
    {
        return $this->request('DELETE', $this->buildListUrl('unsubscribe'), ['email' => $email]);
    }

    /**
     * @param string $email
     * @return array
     * @throws IntegrationException
     */
    public function getSubscriber(string $email): array
    {
        return $this->request('GET', self::API_URL . 'subscribers/' . rawurlencode($email));
    }

    /**
     * @param null $apiKey
     * @return array
     * @throws IntegrationException
     */
    public function getSubscriberLists($apiKey = null): array
    {
        $cacheKey = $apiKey ? sha1((string)$apiKey) : 'configured';
        if (isset(self::$subscriberListsCache[$cacheKey])) {
            return self::$subscriberListsCache[$cacheKey];
        }

        self::$subscriberListsCache[$cacheKey] = $this->request('GET', self::API_URL . 'lists', null, $apiKey);

        return self::$subscriberListsCache[$cacheKey];
    }

    /**
     * @param null $apiKey
     * @return array
     * @throws IntegrationException
     */
    public function getAccount($apiKey = null): array
    {
        return $this->request('GET', self::API_URL . 'account', null, $apiKey);
    }

    /**
     * @param array $data
     * @return array
     * @throws IntegrationException
     */
    public function createTransaction(array $data): array
    {
        return $this->request('POST', self::API_URL . 'tracker/transaction', $data);
    }

    /**
     * @param array $data
     * @return array
     * @throws IntegrationException
     */
    public function updateTransaction(array $data): array
    {
        $orderId = $data['transaction']['order_id'] ?? '';

        return $this->request('PUT', self::API_URL . 'tracker/transaction/' . rawurlencode((string)$orderId), $data);
    }

    /**
     * @param $data
     * @return array
     * @throws IntegrationException
     */
    public function updateCart($data): array
    {
        return $this->request('POST', self::API_URL . 'tracker/events', $data);
    }

    /**
     * @param array $subscriberData
     * @return array
     * @throws IntegrationException
     */
    public function updateSubscriberInList(array $subscriberData): array
    {
        $data = $subscriberData['subscriber_data'] ?? $subscriberData;
        $email = $data['email'] ?? $subscriberData['email'] ?? '';

        if ($email && isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = $this->mergeSubscriberTags($email, $data['tags']);
        }

        return $this->request(
            'PUT',
            $this->buildListUrl('update-subscriber'),
            [
                'email' => $email,
                'subscriber_data' => $data,
            ]
        );
    }

    /**
     * @param array $subscriberData
     * @return array
     * @throws IntegrationException
     */
    public function bulkSubscribeToList(
        array $subscriberData,
        bool $updateExisting = true,
        bool $includeTags = false
    ): array
    {
        $subscriberData = array_slice($subscriberData, 0, self::MAX_BULK_SUBSCRIBERS);

        return $this->request(
            'POST',
            $this->buildListUrl('subscribe-bulk'),
            [
                'subscriber_data' => $includeTags ? $subscriberData : $this->removeBulkTags($subscriberData),
                'resubscribe' => false,
                'update_existing' => $updateExisting,
                'skip_confirmation' => false,
                'trigger_autoresponders' => false,
            ]
        );
    }

    /**
     * @param array $transactionData
     * @return array
     * @throws IntegrationException
     */
    public function bulkOrders(array $transactionData): array
    {
        return $this->request(
            'POST',
            self::API_URL . 'tracker/transaction-bulk',
            ['transaction_data' => array_slice($transactionData, 0, self::MAX_BULK_TRANSACTIONS)]
        );
    }

    /**
     * @param array $params
     * @return array
     * @throws IntegrationException
     */
    public function magentoInstalled(array $params = []): array
    {
        return $this->request(
            'POST',
            self::API_NEW_URL . 'webhooks/magento-install',
            array_merge(['key' => $this->helper->getApiKey()], $params)
        );
    }

    /**
     * @return array
     * @throws IntegrationException
     */
    public function magentoUninstalled(): array
    {
        return $this->request(
            'POST',
            self::API_NEW_URL . 'webhooks/magento-uninstall',
            ['key' => $this->helper->getApiKey()]
        );
    }

    /**
     * @param int $status
     * @param string $body
     * @return array
     * @throws IntegrationException
     */
    private function processResponse(int $status, string $body): array
    {
        $response = [];

        if ($body !== '') {
            try {
                $response = $this->jsonSerializer->unserialize($body);
            } catch (\InvalidArgumentException $e) {
                if ($status === self::STATUS_OK || $status === self::STATUS_CREATED) {
                    return [];
                }

                throw new IntegrationException(__(
                    'Ecomail api error: Invalid JSON response. HTTP status: %1. Response: %2',
                    $status ?: 'unknown',
                    $this->getResponseSnippet($body)
                ));
            }
        }

        if (!is_array($response)) {
            $response = [];
        }

        if ($status !== self::STATUS_OK && $status !== self::STATUS_CREATED) {
            throw new IntegrationException(__($this->getErrorMessage($response)));
        }

        return $response;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array|null $data
     * @param null $apiKey
     * @return array
     * @throws IntegrationException
     */
    private function request(string $method, string $uri, ?array $data = null, $apiKey = null): array
    {
        $start = microtime(true);
        $status = null;
        $message = null;

        if ($apiKey === null) {
            $apiKey = $this->helper->getApiKey();
        }

        $ch = curl_init($uri);

        if ($ch === false) {
            throw new IntegrationException(__('Ecomail api error: Unable to initialize HTTP client.'));
        }

        $headers = [
            'Content-Type: application/json',
            'Key: ' . (string)$apiKey,
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CLIENT_TIMEOUT);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null && $method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->jsonSerializer->serialize($data));
        }

        try {
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                $message = $error ?: self::UNKNOWN_ERROR_MESSAGE;
                throw new IntegrationException(__('Ecomail api error: %1', $message));
            }

            $response = $this->processResponse($status, (string)$body);
            $this->logRequest($method, $uri, $status, true, $this->getDurationMs($start));

            return $response;
        } catch (IntegrationException $e) {
            $message = $message ?: $e->getMessage();
            $this->logRequest($method, $uri, $status, false, $this->getDurationMs($start), $message);

            throw $e;
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param int|null $status
     * @param bool $success
     * @param int $durationMs
     * @param string|null $message
     */
    private function logRequest(
        string $method,
        string $uri,
        ?int $status,
        bool $success,
        int $durationMs,
        ?string $message = null
    ): void {
        if ($this->apiLog === null) {
            return;
        }

        $this->apiLog->log($method, $uri, $status, $success, $durationMs, $message);
    }

    /**
     * Bulk subscribe can update existing contacts. Because Ecomail replaces tags
     * when tags are sent, bulk imports omit tags to preserve user-managed tags.
     *
     * @param array $subscribers
     * @return array
     */
    private function removeBulkTags(array $subscribers): array
    {
        foreach ($subscribers as &$subscriber) {
            unset($subscriber['tags']);
        }

        return $subscribers;
    }

    /**
     * @param string $email
     * @param array $newTags
     * @return array
     */
    private function mergeSubscriberTags(string $email, array $newTags): array
    {
        try {
            $subscriber = $this->getSubscriber($email);
            $existingTags = $subscriber['subscriber']['tags'] ?? [];

            if (!is_array($existingTags)) {
                $existingTags = [];
            }

            return array_values(array_unique(array_filter(array_merge($existingTags, $newTags))));
        } catch (IntegrationException $e) {
            return array_values(array_unique(array_filter($newTags)));
        }
    }

    /**
     * @param float $start
     * @return int
     */
    private function getDurationMs(float $start): int
    {
        return (int)round((microtime(true) - $start) * 1000);
    }

    /**
     * @param string $path
     * @return string
     */
    private function buildListUrl(string $path): string
    {
        return self::API_URL . 'lists/' . $this->helper->getSubscriberList() . '/' . $path;
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

    /**
     * @param string $body
     * @return string
     */
    private function getResponseSnippet(string $body): string
    {
        $body = trim(preg_replace('/\s+/', ' ', $body));

        if ($body === '') {
            return self::UNKNOWN_ERROR_MESSAGE;
        }

        return substr($body, 0, 180);
    }
}

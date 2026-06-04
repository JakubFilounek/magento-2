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
    const STATUS_NO_CONTENT = 204;
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
        $this->validateEmail($email);

        return $this->request('DELETE', $this->buildListUrl('unsubscribe'), ['email' => $email]);
    }

    /**
     * @param string $email
     * @return array
     * @throws IntegrationException
     */
    public function getSubscriber(string $email): array
    {
        $this->validateEmail($email);

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
        $this->validateTransaction($data);

        return $this->request('POST', self::API_URL . 'tracker/transaction', $data);
    }

    /**
     * @param array $data
     * @return array
     * @throws IntegrationException
     */
    public function updateTransaction(array $data): array
    {
        $this->validateTransaction($data);
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
        $this->validateEmail((string)$email);

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
        $subscriberData = array_slice($this->filterValidSubscribers($subscriberData), 0, self::MAX_BULK_SUBSCRIBERS);

        if (!$subscriberData) {
            throw new IntegrationException(__('Ecomail api error: No valid subscribers to send.'));
        }

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
        $transactionData = array_slice($this->filterValidTransactions($transactionData), 0, self::MAX_BULK_TRANSACTIONS);

        if (!$transactionData) {
            throw new IntegrationException(__('Ecomail api error: No valid transactions to send.'));
        }

        return $this->request(
            'POST',
            self::API_URL . 'tracker/transaction-bulk',
            ['transaction_data' => $transactionData]
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
                if ($this->isSuccessfulStatus($status)) {
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

        if (!$this->isSuccessfulStatus($status)) {
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

        if (trim((string)$apiKey) === '') {
            throw new IntegrationException(__('Ecomail api error: API key is not configured.'));
        }

        $payload = null;
        if ($data !== null && $method !== 'GET') {
            try {
                $payload = $this->jsonSerializer->serialize($data);
            } catch (\InvalidArgumentException $e) {
                throw new IntegrationException(__('Ecomail api error: Unable to encode request data.'));
            }
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

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
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

        try {
            $this->apiLog->log($method, $uri, $status, $success, $durationMs, $message);
        } catch (\Exception $e) {
            // API logging must not affect the storefront, checkout, or sync result.
        }
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
        $subscriberList = trim((string)$this->helper->getSubscriberList());

        if ($subscriberList === '') {
            throw new IntegrationException(__('Ecomail api error: Subscriber list is not configured.'));
        }

        return self::API_URL . 'lists/' . rawurlencode($subscriberList) . '/' . $path;
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
            $messages = $this->flattenErrorMessages($response['errors']);

            if ($messages) {
                return __('Ecomail api error: ') . implode(', ', $messages);
            }
        }

        return self::UNKNOWN_ERROR_MESSAGE;
    }

    /**
     * @param int $status
     * @return bool
     */
    private function isSuccessfulStatus(int $status): bool
    {
        return in_array($status, [self::STATUS_OK, self::STATUS_CREATED, self::STATUS_NO_CONTENT], true);
    }

    /**
     * @param string $email
     * @throws IntegrationException
     */
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new IntegrationException(__('Ecomail api error: Invalid subscriber email.'));
        }
    }

    /**
     * @param array $transaction
     * @throws IntegrationException
     */
    private function validateTransaction(array $transaction): void
    {
        $data = $transaction['transaction'] ?? [];

        if (empty($data['order_id']) || empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new IntegrationException(__('Ecomail api error: Invalid transaction data.'));
        }
    }

    /**
     * @param array $subscribers
     * @return array
     */
    private function filterValidSubscribers(array $subscribers): array
    {
        return array_values(array_filter($subscribers, function ($subscriber) {
            return is_array($subscriber)
                && !empty($subscriber['email'])
                && filter_var($subscriber['email'], FILTER_VALIDATE_EMAIL);
        }));
    }

    /**
     * @param array $transactions
     * @return array
     */
    private function filterValidTransactions(array $transactions): array
    {
        return array_values(array_filter($transactions, function ($transaction) {
            $data = is_array($transaction) ? ($transaction['transaction'] ?? []) : [];

            return is_array($data)
                && !empty($data['order_id'])
                && !empty($data['email'])
                && filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        }));
    }

    /**
     * @param mixed $errors
     * @return array
     */
    private function flattenErrorMessages($errors): array
    {
        if (is_scalar($errors)) {
            return [(string)$errors];
        }

        if (!is_array($errors)) {
            return [];
        }

        $messages = [];
        foreach ($errors as $error) {
            foreach ($this->flattenErrorMessages($error) as $message) {
                if ($message !== '') {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * @param string $body
     * @return string
     */
    private function getResponseSnippet(string $body): string
    {
        $body = trim((string)preg_replace('/\s+/', ' ', $body));

        if ($body === '') {
            return self::UNKNOWN_ERROR_MESSAGE;
        }

        return substr($body, 0, 180);
    }
}

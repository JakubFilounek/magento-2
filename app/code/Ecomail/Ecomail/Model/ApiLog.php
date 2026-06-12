<?php

namespace Ecomail\Ecomail\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class ApiLog
{
    private const RETENTION_DAYS = 7;
    private const MAX_ROWS = 10000;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param int|null $statusCode
     * @param bool $success
     * @param int|null $durationMs
     * @param string|null $message
     */
    public function log(
        string $method,
        string $uri,
        ?int $statusCode,
        bool $success,
        ?int $durationMs,
        ?string $message = null
    ): void {
        try {
            $connection = $this->resource->getConnection();
            $connection->insert(
                $this->resource->getTableName('ecomail_api_log'),
                [
                    'method' => substr($method, 0, 10),
                    'endpoint' => substr($this->sanitizeEndpoint($uri), 0, 255),
                    'status_code' => $statusCode,
                    'success' => $success ? 1 : 0,
                    'duration_ms' => $durationMs,
                    'message' => $message ? substr($message, 0, 255) : null,
                ]
            );
        } catch (\Exception $e) {
            $this->logger->debug('Unable to write Ecomail API log.', [$e]);
        }
    }

    /**
     * Remove old rows and cap total row count.
     */
    public function cleanup(): void
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('ecomail_api_log');
            $connection->delete(
                $table,
                ['created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)' => self::RETENTION_DAYS]
            );

            $count = (int)$connection->fetchOne($connection->select()->from($table, 'COUNT(*)'));
            if ($count <= self::MAX_ROWS) {
                return;
            }

            $deleteCount = $count - self::MAX_ROWS;
            $ids = $connection->fetchCol(
                $connection->select()
                    ->from($table, 'log_id')
                    ->order('log_id ASC')
                    ->limit($deleteCount)
            );

            if ($ids) {
                $connection->delete($table, ['log_id IN (?)' => $ids]);
            }
        } catch (\Exception $e) {
            $this->logger->debug('Unable to clean Ecomail API log.', [$e]);
        }
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 20): array
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('ecomail_api_log');

            return $connection->fetchAll(
                $connection->select()
                    ->from($table)
                    ->order('log_id DESC')
                    ->limit(max(1, min(100, $limit)))
            );
        } catch (\Exception $e) {
            $this->logger->debug('Unable to read Ecomail API log.', [$e]);

            return [];
        }
    }

    /**
     * @param string $uri
     * @return string
     */
    private function sanitizeEndpoint(string $uri): string
    {
        $parts = parse_url($uri);
        if (!$parts || !isset($parts['path'])) {
            return $uri;
        }

        return ($parts['host'] ?? '') . $parts['path'];
    }
}

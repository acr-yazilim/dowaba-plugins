<?php
/**
 * Dowaba AI Connector — Audit Logger.
 *
 * Writes every inbound API request to `dowaba_ai_audit`
 * (function_slug, request_ip, status_code, duration_ms, error_message, created_at).
 *
 * Best-effort: a logging failure never breaks the request. Lazy retention purge
 * (1/500 chance per write) keeps the table from growing unbounded without needing
 * a dedicated cron.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class AuditLogger
{
    private const TABLE = 'dowaba_ai_audit';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function write(
        string $functionSlug,
        string $requestIp,
        int $statusCode,
        int $durationMs,
        ?string $errorMessage = null
    ): void {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName(self::TABLE);

            $connection->insert($table, [
                'function_slug' => mb_substr($functionSlug, 0, 64, 'UTF-8'),
                'request_ip'    => mb_substr($requestIp, 0, 45, 'UTF-8'),
                'status_code'   => $statusCode,
                'duration_ms'   => $durationMs,
                'error_message' => $errorMessage !== null ? mb_substr($errorMessage, 0, 500, 'UTF-8') : null,
            ]);

            // Lazy retention cleanup — disk-fill guard without a cron.
            if (random_int(1, 500) === 1) {
                $this->purgeOld($this->config->getAuditRetentionDays());
            }
        } catch (\Throwable $e) {
            // fail-safe: never break the API request because of audit logging
            $this->logger->warning('Dowaba audit write failed: ' . $e->getMessage());
        }
    }

    public function purgeOld(int $retentionDays = 30): int
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName(self::TABLE);
            return (int) $connection->delete(
                $table,
                ['created_at < ?' => date('Y-m-d H:i:s', time() - max(1, $retentionDays) * 86400)]
            );
        } catch (\Throwable $e) {
            return -1;
        }
    }

    /**
     * Last-24h per-function stats for the admin "Audit Log" widget.
     *
     * @return array<int,array{function_slug:string,total:int,avg_duration_ms:int,error_count:int,error_rate:float}>
     */
    public function getStats24h(): array
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName(self::TABLE);
            $select = $connection->select()
                ->from($table, [
                    'function_slug',
                    'total' => new \Zend_Db_Expr('COUNT(*)'),
                    'avg_duration_ms' => new \Zend_Db_Expr('AVG(duration_ms)'),
                    'error_count' => new \Zend_Db_Expr('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END)'),
                ])
                ->where('created_at >= ?', date('Y-m-d H:i:s', time() - 86400))
                ->group('function_slug')
                ->order('total DESC');

            $rows = $connection->fetchAll($select);
            return array_map(static function ($r) {
                $total = (int) $r['total'];
                $errors = (int) $r['error_count'];
                return [
                    'function_slug'   => (string) $r['function_slug'],
                    'total'           => $total,
                    'avg_duration_ms' => (int) round((float) $r['avg_duration_ms']),
                    'error_count'     => $errors,
                    'error_rate'      => $total > 0 ? round($errors / $total * 100, 2) : 0.0,
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Recent audit rows for the admin table.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getRecent(int $limit = 100, ?string $functionSlug = null, ?int $statusCode = null): array
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName(self::TABLE);
            $select = $connection->select()
                ->from($table)
                ->order('audit_id DESC')
                ->limit(max(10, min(500, $limit)));

            if ($functionSlug !== null && $functionSlug !== '') {
                $select->where('function_slug = ?', $functionSlug);
            }
            if ($statusCode !== null) {
                $select->where('status_code = ?', $statusCode);
            }

            return $connection->fetchAll($select);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

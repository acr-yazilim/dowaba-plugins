<?php
/**
 * Dowaba AI Connector — audit log feed (AJAX).
 *
 * Returns recent audit rows + 24h per-function stats for the admin widget.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Controller\Adminhtml\Settings;

use Dowaba\AiConnector\Model\AuditLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class AuditLog extends Action
{
    public const ADMIN_RESOURCE = 'Dowaba_AiConnector::settings';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly AuditLogger $auditLogger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $request = $this->getRequest();

        $limit = max(10, min(500, (int) $request->getParam('limit', 100)));
        $functionSlug = trim((string) $request->getParam('function_slug', '')) ?: null;
        $statusFilter = $request->getParam('status') !== null && $request->getParam('status') !== ''
            ? (int) $request->getParam('status')
            : null;

        $rows = $this->auditLogger->getRecent($limit, $functionSlug, $statusFilter);

        return $result->setData([
            'success' => true,
            'count'   => count($rows),
            'stats'   => $this->auditLogger->getStats24h(),
            'rows'    => array_map(static fn($r) => [
                'audit_id'      => (int) $r['audit_id'],
                'function_slug' => $r['function_slug'],
                'request_ip'    => $r['request_ip'],
                'status_code'   => (int) $r['status_code'],
                'duration_ms'   => (int) $r['duration_ms'],
                'error_message' => $r['error_message'],
                'created_at'    => $r['created_at'],
            ], $rows),
        ]);
    }
}

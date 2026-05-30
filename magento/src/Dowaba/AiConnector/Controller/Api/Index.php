<?php
/**
 * Dowaba AI Connector — API dispatcher controller.
 *
 * GET/POST /dowaba_ai/api?action=<x> — runs one of the 10 manifest functions.
 *
 * Per request:
 *   1. Auth::verify     — module on + Bearer token + IP whitelist + last_used touch
 *   2. ScopeGuard::check — read/write toggle
 *   3. Dispatcher::<fn>  — store logic
 *   4. AuditLogger::write — every response (incl. auth failures) is logged
 *
 * CSRF is intentionally bypassed (CsrfAwareActionInterface) — these are
 * machine-to-machine calls authenticated by Bearer token, not browser form posts.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Controller\Api;

use Dowaba\AiConnector\Model\Api\Dispatcher;
use Dowaba\AiConnector\Model\AuditLogger;
use Dowaba\AiConnector\Model\Auth;
use Dowaba\AiConnector\Model\ScopeGuard;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /** action → [slug, required scope, dispatcher method]. Whitelist = no arbitrary method call. */
    private const ACTIONS = [
        'products'        => ['slug' => 'mgm_product_search',  'scope' => 'read',  'fn' => 'products'],
        'product'         => ['slug' => 'mgm_product_detail',  'scope' => 'read',  'fn' => 'product'],
        'compare'         => ['slug' => 'mgm_product_compare', 'scope' => 'read',  'fn' => 'compare'],
        'stock'           => ['slug' => 'mgm_stock_check',     'scope' => 'read',  'fn' => 'stock'],
        'categories'      => ['slug' => 'mgm_category_list',   'scope' => 'read',  'fn' => 'categories'],
        'order'           => ['slug' => 'mgm_order_status',    'scope' => 'read',  'fn' => 'order'],
        'customer_lookup' => ['slug' => 'mgm_customer_lookup', 'scope' => 'read',  'fn' => 'customerLookup'],
        'cart_recover'    => ['slug' => 'mgm_cart_recover',    'scope' => 'read',  'fn' => 'cartRecover'],
        'order_preview'   => ['slug' => 'mgm_order_preview',   'scope' => 'write', 'fn' => 'orderPreview'],
        'order_confirm'   => ['slug' => 'mgm_order_confirm',   'scope' => 'write', 'fn' => 'orderConfirm'],
    ];

    private float $startTime;
    private string $clientIp = '0.0.0.0';

    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly HttpRequest $request,
        private readonly Auth $auth,
        private readonly ScopeGuard $scopeGuard,
        private readonly AuditLogger $auditLogger,
        private readonly Dispatcher $dispatcher,
        private readonly JsonSerializer $jsonSerializer,
        private readonly LoggerInterface $logger
    ) {
        $this->startTime = microtime(true);
    }

    public function execute()
    {
        $action = (string) $this->request->getParam('action', '');

        if (!isset(self::ACTIONS[$action])) {
            return $this->respond('unknown', 400, [
                'error' => 'Invalid or missing action parameter',
                'valid_actions' => array_keys(self::ACTIONS),
            ]);
        }

        $meta = self::ACTIONS[$action];

        // 1) Auth
        $authResult = $this->auth->verify($this->request);
        $this->clientIp = $authResult['client_ip'];
        if (!$authResult['success']) {
            return $this->respond($meta['slug'], $authResult['status'], ['error' => $authResult['error']]);
        }

        // 2) Scope guard
        $scope = $this->scopeGuard->check($meta['scope']);
        if (!$scope['allowed']) {
            return $this->respond($meta['slug'], $scope['status'], ['error' => $scope['error']]);
        }

        // 3) Dispatch
        $params = $this->collectParams();
        try {
            $fn = $meta['fn'];
            $result = $this->dispatcher->$fn($params);
        } catch (\Throwable $e) {
            $this->logger->error('Dowaba dispatch ' . $action . ' failed: ' . $e->getMessage());
            return $this->respond($meta['slug'], 500, [
                'error'  => 'internal error',
                'detail' => $e->getMessage(),
            ]);
        }

        return $this->respond($meta['slug'], (int) $result['status'], (array) $result['body']);
    }

    /**
     * Merge GET/form params with a JSON request body (write functions post JSON).
     */
    private function collectParams(): array
    {
        $params = $this->request->getParams();

        $content = (string) $this->request->getContent();
        if ($content !== '') {
            try {
                $json = $this->jsonSerializer->unserialize($content);
                if (is_array($json) && !empty($json)) {
                    $params = array_merge($params, $json);
                }
            } catch (\Throwable $e) {
                // not JSON — form params already captured above
            }
        }

        return $params;
    }

    private function respond(string $slug, int $status, array $payload)
    {
        $duration = (int) round((microtime(true) - $this->startTime) * 1000);

        $this->auditLogger->write(
            $slug,
            $this->clientIp,
            $status,
            $duration,
            $status >= 400 ? (string) ($payload['error'] ?? null) : null
        );

        $result = $this->jsonFactory->create();
        $result->setHttpResponseCode($status);
        $result->setHeader('X-Dowaba-Duration', (string) $duration);
        $result->setData($payload);
        return $result;
    }

    // -------------------------------------------------------------- CSRF (bypass)

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

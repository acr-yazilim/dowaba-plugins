<?php
namespace Opencart\Catalog\Controller\Extension\DowabaAi;

/**
 * Dowaba AI — Catalog REST API Controller
 *
 * Manifest'te tanımlı 10 function'ın endpoint implementasyonu.
 *
 * Routing convention (OC4):
 *   /index.php?route=extension/dowaba_ai/api.<method>
 *   Örn: ?route=extension/dowaba_ai/api.products&q=iphone&limit=10
 *
 * Auth: Her method başında self::guard() çağrılır:
 *   - Bearer token verify (Auth.php — Faz 2)
 *   - IP whitelist
 *   - Scope (read/write)
 *   - Audit log (AuditLogger.php — Faz 2)
 *
 * Şu an (Faz 1): Auth + scope stub'lar — Faz 2'de gerçek implementation gelecek.
 */
class Api extends \Opencart\System\Engine\Controller {

    private float $startTime;
    private string $currentSlug = '';

    public function __construct($registry) {
        parent::__construct($registry);
        $this->startTime = microtime(true);
    }

    // ============================================================ READ functions

    public function products(): void {
        $this->currentSlug = 'opc_product_search';
        if (!$this->guard('read')) return;

        $query = trim((string) ($this->request->get['q'] ?? ''));
        $limit = max(1, min(50, (int) ($this->request->get['limit'] ?? 10)));

        if ($query === '') {
            $this->respond(400, ['error' => 'q parameter required']);
            return;
        }

        $this->load->model('catalog/product');

        // OC4 getProducts filter format
        $filterData = [
            'filter_name'  => $query,
            'sort'         => 'p.sort_order',
            'order'        => 'ASC',
            'start'        => 0,
            'limit'        => $limit,
        ];
        $products = $this->model_catalog_product->getProducts($filterData);

        $data = [];
        foreach ($products as $p) {
            $data[] = $this->shapeProduct($p);
        }

        $this->respond(200, ['data' => $data, 'count' => count($data), 'query' => $query]);
    }

    public function product(): void {
        $this->currentSlug = 'opc_product_detail';
        if (!$this->guard('read')) return;

        $productId = (int) ($this->request->get['id'] ?? 0);
        if ($productId <= 0) {
            $this->respond(400, ['error' => 'id parameter required']);
            return;
        }

        $this->load->model('catalog/product');
        $p = $this->model_catalog_product->getProduct($productId);

        if (!$p) {
            $this->respond(404, ['error' => 'product not found', 'product_id' => $productId]);
            return;
        }

        $attributes = method_exists($this->model_catalog_product, 'getAttributes')
            ? $this->model_catalog_product->getAttributes($productId)
            : [];

        $this->respond(200, [
            'data' => array_merge(
                $this->shapeProduct($p),
                [
                    'description' => strip_tags((string) ($p['description'] ?? '')),
                    'model'       => $p['model']     ?? '',
                    'sku'         => $p['sku']       ?? '',
                    'weight'      => $p['weight']    ?? null,
                    'attributes'  => $this->shapeAttributes($attributes),
                ]
            ),
        ]);
    }

    public function compare(): void {
        $this->currentSlug = 'opc_product_compare';
        if (!$this->guard('read')) return;

        $idsRaw = $this->request->get['ids'] ?? '';
        $ids = is_array($idsRaw) ? $idsRaw : explode(',', (string) $idsRaw);
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, fn($id) => $id > 0);
        $ids = array_values(array_unique($ids));

        if (count($ids) < 2 || count($ids) > 3) {
            $this->respond(400, ['error' => 'ids must contain 2 or 3 unique product IDs', 'received' => $ids]);
            return;
        }

        $this->load->model('catalog/product');

        $products = [];
        $allAttributes = [];

        foreach ($ids as $pid) {
            $p = $this->model_catalog_product->getProduct($pid);
            if (!$p) continue;
            $attrs = method_exists($this->model_catalog_product, 'getAttributes')
                ? $this->model_catalog_product->getAttributes($pid) : [];
            $shapedAttrs = $this->shapeAttributes($attrs);

            $products[] = array_merge(
                $this->shapeProduct($p),
                ['attributes' => $shapedAttrs]
            );

            foreach ($shapedAttrs as $name => $val) {
                $allAttributes[$name] ??= [];
                $allAttributes[$name][$pid] = $val;
            }
        }

        if (count($products) < 2) {
            $this->respond(404, ['error' => 'not enough valid products found', 'received_ids' => $ids]);
            return;
        }

        // Common attributes (all products have same value) vs unique differences
        $common = [];
        $differences = [];
        foreach ($allAttributes as $name => $values) {
            if (count($values) === count($products) && count(array_unique($values)) === 1) {
                $common[$name] = reset($values);
            } else {
                $differences[$name] = $values;
            }
        }

        $this->respond(200, [
            'data' => [
                'products'           => $products,
                'common_attributes'  => $common,
                'unique_differences' => $differences,
                'price_range' => [
                    'min' => min(array_column($products, 'price')),
                    'max' => max(array_column($products, 'price')),
                ],
            ],
        ]);
    }

    public function stock(): void {
        $this->currentSlug = 'opc_stock_check';
        if (!$this->guard('read')) return;

        $productId = (int) ($this->request->get['product_id'] ?? 0);
        $sku = trim((string) ($this->request->get['sku'] ?? ''));

        if ($productId <= 0 && $sku === '') {
            $this->respond(400, ['error' => 'product_id or sku required']);
            return;
        }

        $this->load->model('catalog/product');
        $product = null;

        if ($productId > 0) {
            $product = $this->model_catalog_product->getProduct($productId);
        } elseif ($sku !== '') {
            // OC4: filter by sku
            $filtered = $this->model_catalog_product->getProducts(['filter_sku' => $sku, 'start' => 0, 'limit' => 1]);
            $product = $filtered[0] ?? null;
        }

        if (!$product) {
            $this->respond(404, ['error' => 'product not found']);
            return;
        }

        $stock = (int) ($product['quantity'] ?? 0);
        $this->respond(200, [
            'data' => [
                'product_id' => (int) $product['product_id'],
                'name'       => $product['name']  ?? '',
                'sku'        => $product['sku']   ?? '',
                'stock'      => $stock,
                'in_stock'   => $stock > 0,
                'eta_days'   => $stock > 0 ? 0 : null,
            ],
        ]);
    }

    public function categories(): void {
        $this->currentSlug = 'opc_category_list';
        if (!$this->guard('read')) return;

        $parentId = max(0, (int) ($this->request->get['parent_id'] ?? 0));

        $this->load->model('catalog/category');
        $rows = $this->model_catalog_category->getCategories($parentId);

        $data = [];
        foreach ($rows as $c) {
            $data[] = [
                'category_id' => (int) $c['category_id'],
                'name'        => $c['name'] ?? '',
                'parent_id'   => (int) ($c['parent_id'] ?? 0),
            ];
        }

        $this->respond(200, ['data' => $data, 'count' => count($data), 'parent_id' => $parentId]);
    }

    public function order(): void {
        $this->currentSlug = 'opc_order_status';
        if (!$this->guard('read')) return;

        $orderId = (int) ($this->request->get['id'] ?? 0);
        $email = strtolower(trim((string) ($this->request->get['email'] ?? '')));

        if ($orderId <= 0 || $email === '') {
            $this->respond(400, ['error' => 'id and email required']);
            return;
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($orderId);

        if (!$order || strtolower((string) ($order['email'] ?? '')) !== $email) {
            // Generic 404 — IDOR koruması: doğru order_id + yanlış email değil "yok" der
            $this->respond(404, ['error' => 'order not found']);
            return;
        }

        $statusName = $order['order_status'] ?? '';
        $this->respond(200, [
            'data' => [
                'order_id'       => (int) $order['order_id'],
                'status'         => $statusName,
                'total'          => (float) ($order['total'] ?? 0),
                'currency'       => $order['currency_code'] ?? '',
                'created_at'     => $order['date_added']    ?? null,
                'modified_at'    => $order['date_modified'] ?? null,
                'shipping_city'  => $order['shipping_city'] ?? '',
                'shipping_country' => $order['shipping_country'] ?? '',
            ],
        ]);
    }

    public function customerLookup(): void {
        $this->currentSlug = 'opc_customer_lookup';
        if (!$this->guard('read')) return;

        $phone = trim((string) ($this->request->get['phone'] ?? ''));
        $email = strtolower(trim((string) ($this->request->get['email'] ?? '')));

        if ($phone === '' && $email === '') {
            $this->respond(400, ['error' => 'phone or email required']);
            return;
        }

        $this->load->model('account/customer');

        $customer = null;
        if ($email !== '') {
            $customer = $this->model_account_customer->getCustomerByEmail($email);
        }
        // Phone-only lookup OC4 default'ta yok — telephone column üzerinden manuel
        if (!$customer && $phone !== '') {
            $normPhone = preg_replace('/\D+/', '', $phone);
            $rows = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer` WHERE REPLACE(REPLACE(REPLACE(telephone, ' ', ''), '-', ''), '+', '') = '" . $this->db->escape($normPhone) . "' LIMIT 1");
            $customer = $rows->row ?? null;
        }

        if (!$customer) {
            $this->respond(404, ['error' => 'customer not found']);
            return;
        }

        // Son 5 sipariş
        $orders = $this->db->query("SELECT order_id, total, currency_code, date_added FROM `" . DB_PREFIX . "order` WHERE customer_id = " . (int) $customer['customer_id'] . " ORDER BY order_id DESC LIMIT 5")->rows;

        $this->respond(200, [
            'data' => [
                'customer_id' => (int) $customer['customer_id'],
                'name'        => trim(($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? '')),
                'email'       => $customer['email'] ?? '',
                'phone'       => $customer['telephone'] ?? '',
                'created_at'  => $customer['date_added'] ?? null,
                'recent_orders' => array_map(fn($o) => [
                    'order_id' => (int) $o['order_id'],
                    'total'    => (float) $o['total'],
                    'currency' => $o['currency_code'] ?? '',
                    'date'     => $o['date_added'] ?? null,
                ], $orders),
            ],
        ]);
    }

    public function cartRecover(): void {
        $this->currentSlug = 'opc_cart_recover';
        if (!$this->guard('read')) return;

        $body = $this->readJsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $customerId = (int) ($body['customer_id'] ?? 0);

        if ($email === '' && $customerId <= 0) {
            $this->respond(400, ['error' => 'email or customer_id required']);
            return;
        }

        // Re-engagement link: storefront login URL + (gelecekte) HMAC signed token ile auto-login
        $base = rtrim($this->config->get('config_url'), '/');
        $token = bin2hex(random_bytes(16));
        $link = $base . '/index.php?route=account/login&recover=' . $token;

        $this->respond(200, [
            'data' => [
                'recover_link' => $link,
                'expires_in'   => 86400,
                'note'         => 'Token Faz 2\'de DB\'ye kaydedilip auto-login için bind edilecek',
            ],
        ]);
    }

    // ============================================================ WRITE functions (Faz 3'te detaylanacak)

    public function orderPreview(): void {
        $this->currentSlug = 'opc_order_preview';
        if (!$this->guard('write')) return;

        // TODO Faz 3: OrderPreview.php cache + total recalculation + preview_id üret
        $this->respond(501, ['error' => 'order_preview implementation Faz 3\'te tamamlanacak']);
    }

    public function orderConfirm(): void {
        $this->currentSlug = 'opc_order_confirm';
        if (!$this->guard('write')) return;

        // TODO Faz 3: preview_id verify + cache consume + actual order create
        $this->respond(501, ['error' => 'order_confirm implementation Faz 3\'te tamamlanacak']);
    }

    // ============================================================ helpers

    /**
     * Auth + scope + audit guard.
     * Faz 1: STUB — Faz 2'de gerçek Auth.php + ScopeGuard.php + AuditLogger.php devreye girecek.
     *
     * @return bool true if request authorized
     */
    private function guard(string $requiredScope): bool {
        // Module aktif mi?
        if (!$this->config->get('module_dowaba_ai_status')) {
            $this->respond(503, ['error' => 'Dowaba AI module is disabled']);
            return false;
        }

        // Authorization header
        $authHeader = $this->getAuthHeader();
        if ($authHeader === '' || !preg_match('/^Bearer\s+(\S+)$/i', $authHeader, $m)) {
            $this->respond(401, ['error' => 'Bearer token required']);
            return false;
        }
        $providedKey = $m[1];

        // Stored hash
        $storedHash = (string) $this->config->get('module_dowaba_ai_api_key_hash');
        if ($storedHash === '') {
            $this->respond(503, ['error' => 'API key not yet generated — admin must regenerate']);
            return false;
        }

        // Constant-time compare
        if (!hash_equals($storedHash, hash('sha256', $providedKey))) {
            $this->respond(401, ['error' => 'Invalid bearer token']);
            return false;
        }

        // Scope check
        $scopeSetting = 'module_dowaba_ai_scope_' . $requiredScope;
        if (!$this->config->get($scopeSetting)) {
            $this->respond(403, ['error' => 'Scope "' . $requiredScope . '" is disabled in plugin settings']);
            return false;
        }

        // IP whitelist (Faz 2'de Auth.php'ye taşınacak — şimdilik basit inline)
        $ipWhitelist = trim((string) $this->config->get('module_dowaba_ai_ip_whitelist'));
        if ($ipWhitelist !== '') {
            $allowed = array_map('trim', explode(',', $ipWhitelist));
            $clientIp = $this->getClientIp();
            if (!in_array($clientIp, $allowed, true)) {
                $this->respond(403, ['error' => 'IP not whitelisted', 'your_ip' => $clientIp]);
                return false;
            }
        }

        return true;
    }

    private function getAuthHeader(): string {
        // OC4 request header'ları SERVER super'inde
        $hdr = $this->request->server['HTTP_AUTHORIZATION'] ?? '';
        if ($hdr === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $hdr = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
        }
        return (string) $hdr;
    }

    private function getClientIp(): string {
        return (string) ($this->request->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function readJsonBody(): array {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function respond(int $status, array $payload): void {
        $duration = (int) round((microtime(true) - $this->startTime) * 1000);

        // TODO Faz 2: AuditLogger::write($this->currentSlug, $this->getClientIp(), $status, $duration, $payload['error'] ?? null)

        http_response_code($status);
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('X-Dowaba-Duration: ' . $duration);
        $this->response->setOutput(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function shapeProduct(array $p): array {
        $price = (float) ($p['price'] ?? 0);
        $special = (float) ($p['special'] ?? 0);
        $finalPrice = ($special > 0 && $special < $price) ? $special : $price;

        return [
            'product_id' => (int) ($p['product_id'] ?? 0),
            'name'       => $p['name']         ?? '',
            'price'      => $finalPrice,
            'original_price' => $special > 0 ? $price : null,
            'currency'   => $this->config->get('config_currency') ?: 'TRY',
            'stock'      => (int) ($p['quantity'] ?? 0),
            'in_stock'   => ((int) ($p['quantity'] ?? 0)) > 0,
            'url'        => rtrim($this->config->get('config_url'), '/') . '/index.php?route=product/product&product_id=' . (int) ($p['product_id'] ?? 0),
            'thumb'      => $p['image'] ?? null,
        ];
    }

    private function shapeAttributes(array $attributes): array {
        $flat = [];
        foreach ($attributes as $group) {
            $items = $group['attribute'] ?? [];
            foreach ($items as $a) {
                $name = $a['name'] ?? null;
                $text = $a['text'] ?? null;
                if ($name !== null && $text !== null) {
                    $flat[$name] = $text;
                }
            }
        }
        return $flat;
    }
}

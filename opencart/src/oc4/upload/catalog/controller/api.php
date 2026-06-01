<?php
namespace Opencart\Catalog\Controller\Extension\DowabaAi;

// Library'ler OC4 startup/extension tarafından autoload edilir
// (registered namespace: Opencart\System\Library\Extension\DowabaAi → extension/dowaba_ai/system/library/)
use Opencart\System\Library\Extension\DowabaAi\Auth;
use Opencart\System\Library\Extension\DowabaAi\ScopeGuard;
use Opencart\System\Library\Extension\DowabaAi\AuditLogger;
use Opencart\System\Library\Extension\DowabaAi\OrderPreview;

/**
 * Dowaba AI — Catalog REST API Controller
 *
 * Manifest'te tanımlı 10 function'ın endpoint implementasyonu.
 *
 * Routing convention (OC4):
 *   /index.php?route=extension/dowaba_ai/api.<method>
 *   Örn: ?route=extension/dowaba_ai/api.products&q=iphone&limit=10
 *
 * Auth flow (her method başında self::guard($scope) çağrılır):
 *   1. Auth::verify        — module aktif + bearer token + IP whitelist + last_used update
 *   2. ScopeGuard::check   — read/write toggle
 *   3. AuditLogger::write  — respond() içinde, başarı/başarısızlık tüm istekleri loglar
 *
 * Faz 3'te orderPreview/orderConfirm doldurulacak (şu an 501 stub).
 */
class Api extends \Opencart\System\Engine\Controller {

    private float $startTime;
    private string $currentSlug = '';
    private string $clientIp = '0.0.0.0';

    public function __construct($registry) {
        parent::__construct($registry);
        $this->startTime = microtime(true);
    }

    /**
     * Default route handler — query string'deki `action` parametresine göre dispatch.
     *
     * OC4'ün dot/pipe notation'ı bazı HTTP client'larda URL-encode oluyor (Dowaba
     * HttpHandler `.` veya `|` karakterini `%2E` / `%7C` yapıyor) → route resolve
     * fail oluyor. Bunun yerine query param ile dispatch en sağlam çözüm.
     *
     * URL: ?route=extension/dowaba_ai/api&action=products&q=iphone&limit=3
     */
    public function index(): void {
        $action = (string) ($this->request->get['action'] ?? '');

        // Whitelisted dispatcher — saldırgan arbitrary method çağıramasın
        $allowed = [
            'products'         => 'products',
            'product'          => 'product',
            'compare'          => 'compare',
            'stock'            => 'stock',
            'categories'       => 'categories',
            'order'            => 'order',
            'customer_lookup'  => 'customerLookup',
            'cart_recover'     => 'cartRecover',
            'order_preview'    => 'orderPreview',
            'order_confirm'    => 'orderConfirm',
        ];

        if (!isset($allowed[$action])) {
            $this->currentSlug = 'unknown';
            $this->respond(400, [
                'error' => 'Invalid or missing action parameter',
                'valid_actions' => array_keys($allowed),
            ]);
            return;
        }

        $method = $allowed[$action];
        $this->$method();
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

        // Galeri (ek ürün görselleri) — oc_product_image tablosu
        $gallery = [];
        $baseUrl = rtrim((string)($this->config->get('config_url') ?: ''), '/');
        // OC4 catalog/product model metodu `getImages`, OC3'te `getProductImages` idi.
        // KRİTİK: model bir Proxy (__get bilinmeyen key'de Exception fırlatır) — bu yüzden
        // method_exists($proxy, ...) DAİMA false döner → eski method_exists guard'ı OC4'te
        // galeriyi SESSİZCE boş bırakıyordu (gallery_count hep 0). Çözüm: doğrudan çağır,
        // ilki throw ederse diğer sürümün adını dene (hepsi try/catch içinde).
        $rawImages = [];
        try {
            $rawImages = $this->model_catalog_product->getImages($productId);           // OC4
        } catch (\Throwable $e) {
            try {
                $rawImages = $this->model_catalog_product->getProductImages($productId); // OC3 fallback
            } catch (\Throwable $e2) {
                $rawImages = [];
            }
        }
        if (is_array($rawImages) && $rawImages) {
            try {
                $this->load->model('tool/image');
                foreach ($rawImages as $img) {
                    $path = $img['image'] ?? null;
                    if (!$path) continue;
                    try {
                        $gallery[] = [
                            'thumb' => $this->model_tool_image->resize($path, 200, 200),
                            'image' => $this->model_tool_image->resize($path, 600, 600),
                        ];
                    } catch (\Throwable $e) {
                        $gallery[] = [
                            'thumb' => $baseUrl . '/image/' . ltrim($path, '/'),
                            'image' => $baseUrl . '/image/' . ltrim($path, '/'),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Galeri yüklenemese de detail response devam etsin
            }
        }

        $this->respond(200, [
            'data' => array_merge(
                $this->shapeProduct($p),
                [
                    // HTML entity decode + tag strip — AI'a düz metin gitsin
                    'description' => trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) ($p['description'] ?? ''), ENT_QUOTES, 'UTF-8')))),
                    'weight'      => $p['weight']    ?? null,
                    'attributes'  => $this->shapeAttributes($attributes),
                    'gallery'     => $gallery,  // ek görseller (her biri thumb+image full URL)
                    'gallery_count' => count($gallery),
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

    // ============================================================ WRITE functions

    /**
     * Sipariş önizleme (DB'ye yazmaz). Müşteri onayı öncesi AI tarafından çağrılır.
     * Akış:
     *   1. items[] doğrula — her product_id var + stokta + en az qty
     *   2. customer minimum doğrulama (phone veya email zorunlu)
     *   3. Total hesapla (subtotal + shipping)
     *   4. preview_id üret, OrderPreview cache'e koy (TTL 300sn)
     *   5. Yanıt: müşteriye gösterilecek özet
     */
    public function orderPreview(): void {
        $this->currentSlug = 'opc_order_preview';
        if (!$this->guard('write')) return;

        $body = $this->readJsonBody();
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];

        if (empty($items)) {
            $this->respond(400, ['error' => 'items array is required and cannot be empty']);
            return;
        }
        if (count($items) > 50) {
            $this->respond(400, ['error' => 'too many items (max 50)']);
            return;
        }

        // Customer minimum validation — KVKK: en az phone veya email
        $phone = trim((string) ($customer['phone'] ?? ''));
        $email = strtolower(trim((string) ($customer['email'] ?? '')));
        if ($phone === '' && $email === '') {
            $this->respond(400, ['error' => 'customer.phone or customer.email is required']);
            return;
        }

        // Items expand + stock check + price calculation
        $this->load->model('catalog/product');
        $resolvedItems = [];
        $subtotal = 0.0;
        $stockIssues = [];

        foreach ($items as $idx => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));

            if ($productId <= 0) {
                $this->respond(400, ['error' => 'items[' . $idx . '].product_id required']);
                return;
            }

            $product = $this->model_catalog_product->getProduct($productId);
            if (!$product) {
                $this->respond(404, ['error' => 'product not found', 'product_id' => $productId]);
                return;
            }

            $stock = (int) ($product['quantity'] ?? 0);
            if ($stock < $qty) {
                $stockIssues[] = [
                    'product_id'      => $productId,
                    'name'            => $product['name'] ?? '',
                    'requested_qty'   => $qty,
                    'available_stock' => $stock,
                ];
            }

            $unitPrice = $this->effectivePrice($product);
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $resolvedItems[] = [
                'product_id' => $productId,
                'name'       => $product['name'] ?? '',
                'sku'        => $product['sku']  ?? '',
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'stock'      => $stock,
            ];
        }

        // Stok yetersizliği — preview yine de oluştur ama uyar
        // (AI müşteriye "üzgünüz 2 adet yerine 1 adet var, devam edelim mi?" diye sorabilir)
        if (!empty($stockIssues)) {
            $this->respond(409, [
                'error'             => 'insufficient stock',
                'stock_issues'      => $stockIssues,
                'note'              => 'AI'.chr(39).'ya: müşteriye stok yetersizliğini bildir, mevcut adetlerle yeniden preview oluşturmasını teklif et',
            ]);
            return;
        }

        // Shipping: basit flat rate (v0.1)
        // v0.2'de OpenCart shipping module'lerini kullanarak gerçek hesaplama yapılacak
        $shippingCost = $subtotal >= 1000 ? 0.0 : 49.0;

        $total = $subtotal + $shippingCost;

        // Currency
        $currency = $this->config->get('config_currency') ?: 'TRY';

        // Cache'e koy
        $previewId = OrderPreview::generateId();

        $payload = [
            'items'         => $resolvedItems,
            'customer'      => [
                'phone'   => $phone,
                'email'   => $email,
                'name'    => trim((string) ($customer['name']    ?? '')),
                'address' => trim((string) ($customer['address'] ?? '')),
                'city'    => trim((string) ($customer['city']    ?? '')),
            ],
            'subtotal'      => round($subtotal, 2),
            'shipping_cost' => $shippingCost,
            'total'         => round($total, 2),
            'currency'      => $currency,
        ];

        OrderPreview::store($this->registry, $previewId, $payload);

        $this->respond(200, [
            'preview_id' => $previewId,
            'expires_at' => date('c', time() + OrderPreview::TTL_SECONDS),
            'expires_in_seconds' => OrderPreview::TTL_SECONDS,
            'summary'    => $payload,
            'note'       => 'AI\'ya: bu özeti müşteriye göster, "Onaylıyor musun?" diye sor. Müşteri "Evet" derse opc_order_confirm({preview_id, confirmed:true}) çağır.',
        ]);
    }

    /**
     * Müşteri onayı sonrası gerçek siparişi oluştur.
     * preview_id one-shot consume edilir (replay korunur).
     */
    public function orderConfirm(): void {
        $this->currentSlug = 'opc_order_confirm';
        if (!$this->guard('write')) return;

        $body = $this->readJsonBody();
        $previewId = trim((string) ($body['preview_id'] ?? ''));
        $confirmed = filter_var($body['confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // preview_id format check (saldırgan keyfi key inject edemesin)
        if (!OrderPreview::isValidId($previewId)) {
            $this->respond(400, ['error' => 'invalid preview_id format']);
            return;
        }

        if (!$confirmed) {
            $this->respond(400, ['error' => 'confirmed must be true — order_confirm SADECE müşteri açıkça onayladıktan sonra çağrılmalı']);
            return;
        }

        // One-shot consume — cache'den oku ve sil
        $preview = OrderPreview::consume($this->registry, $previewId);
        if ($preview === null) {
            $this->respond(410, [
                'error' => 'preview_id expired or already consumed',
                'note'  => 'AI\'ya: müşteriye yeniden onay almak için yeni opc_order_preview çağır.',
            ]);
            return;
        }

        // v0.1.1 fix: OpenCart order create transaction'a sarıldı.
        // addOrder + addHistory ardışık çağrılır; biri fail olursa rollback ile yarısı kalmasın.
        $this->db->query('START TRANSACTION');
        try {
            $orderId = $this->createOrderFromPreview($preview);
            $this->db->query('COMMIT');
        } catch (\Throwable $e) {
            // Rollback + audit log için error mesajını payload'da göster
            try { $this->db->query('ROLLBACK'); } catch (\Throwable $ignore) {}

            $this->respond(500, [
                'error'  => 'order creation failed',
                'detail' => $e->getMessage(),
                'note'   => 'AI\'ya: müşteriye teknik bir hata oluştu, manuel olarak siparişi tekrar deneyecek. Sipariş veritabanına yazılmadı (rollback).',
            ]);
            return;
        }

        $base = rtrim($this->config->get('config_url'), '/');
        $paymentUrl = $base . '/index.php?route=checkout/success&order_id=' . $orderId;

        $this->respond(200, [
            'data' => [
                'order_id'    => $orderId,
                'status'      => 'pending_payment',
                'payment_url' => $paymentUrl,
                'total'       => $preview['total'],
                'currency'    => $preview['currency'],
            ],
            'note' => 'AI\'ya: müşteriye "Siparişin oluştu #' . $orderId . '. Ödeme link\'i: ..." şeklinde bildir.',
        ]);
    }

    /**
     * Preview payload'undan OpenCart order kaydı oluştur.
     * v0.1: guest order, Cash on Delivery, Flat rate shipping, Pending status.
     * v0.2'de payment module entegrasyonu (PayTR/Iyzico) eklenecek.
     */
    private function createOrderFromPreview(array $preview): int {
        $this->load->model('checkout/order');

        $customer = $preview['customer'];
        $items = $preview['items'];

        // Customer name'i parçala (firstname/lastname)
        $nameParts = preg_split('/\s+/', trim($customer['name']), 2);
        $firstname = $nameParts[0] ?? 'Misafir';
        $lastname = $nameParts[1] ?? 'Müşteri';

        // Products payload — OC4 order_product schema ile birebir
        $orderProducts = array_map(fn($it) => [
            'product_id'   => (int) $it['product_id'],
            'master_id'    => 0,       // OC4: parent_product (variant) için, default 0
            'name'         => $it['name'],
            'model'        => $it['sku'] ?: 'N/A',
            'price'        => $it['unit_price'],
            'total'        => $it['line_total'],
            'tax'          => 0,
            'quantity'     => $it['quantity'],
            'option'       => [],
            'download'     => [],
            'subscription' => [],      // OC4: tekrarlanan abonelik bilgisi, default boş
            'reward'       => 0,
            'subtract'     => 1,       // stoktan düş
        ], $items);

        // Totals payload (OpenCart format)
        $orderTotals = [
            [
                'extension'  => 'opencart',
                'code'       => 'sub_total',
                'title'      => 'Sub-Total',
                'value'      => $preview['subtotal'],
                'sort_order' => 1,
            ],
            [
                'extension'  => 'opencart',
                'code'       => 'shipping',
                'title'      => 'Shipping (Flat rate)',
                'value'      => $preview['shipping_cost'],
                'sort_order' => 2,
            ],
            [
                'extension'  => 'opencart',
                'code'       => 'total',
                'title'      => 'Total',
                'value'      => $preview['total'],
                'sort_order' => 9,
            ],
        ];

        $data = [
            'invoice_prefix'  => $this->config->get('config_invoice_prefix') ?: 'DWB-',
            'store_id'        => 0,
            'store_name'      => $this->config->get('config_name') ?: 'Store',
            'store_url'       => $this->config->get('config_url'),

            'customer_id'       => 0,  // guest
            'customer_group_id' => (int) ($this->config->get('config_customer_group_id') ?: 1),
            'firstname'         => $firstname,
            'lastname'          => $lastname,
            'email'             => $customer['email'] ?: 'guest@dowaba.local',
            'telephone'         => $customer['phone'],
            'custom_field'      => [],
            // OC4 4.0.2.3 — order-level address ID'leri (guest order için 0)
            'payment_address_id'  => 0,
            'shipping_address_id' => 0,

            // Payment — Cash on Delivery default (v0.1)
            'payment_firstname'   => $firstname,
            'payment_lastname'    => $lastname,
            'payment_company'     => '',
            'payment_address_1'   => $customer['address'] ?: '—',
            'payment_address_2'   => '',
            'payment_city'        => $customer['city'] ?: '—',
            'payment_postcode'    => '',
            'payment_country'     => 'Türkiye',
            'payment_country_id'  => 215,  // TR
            'payment_zone'        => '',
            'payment_zone_id'     => 0,
            'payment_address_format' => '',
            'payment_custom_field' => [],
            'payment_method'      => ['code' => 'cod', 'name' => 'Cash on Delivery'],

            // Shipping — aynı adres
            'shipping_firstname'   => $firstname,
            'shipping_lastname'    => $lastname,
            'shipping_company'     => '',
            'shipping_address_1'   => $customer['address'] ?: '—',
            'shipping_address_2'   => '',
            'shipping_city'        => $customer['city'] ?: '—',
            'shipping_postcode'    => '',
            'shipping_country'     => 'Türkiye',
            'shipping_country_id'  => 215,
            'shipping_zone'        => '',
            'shipping_zone_id'     => 0,
            'shipping_address_format' => '',
            'shipping_custom_field' => [],
            'shipping_method'      => ['code' => 'flat.flat', 'name' => 'Flat Shipping Rate'],

            'comment' => 'Sipariş Dowaba AI üzerinden oluşturuldu (preview_id: ' . ($preview['_preview_id'] ?? '') . ')',
            'total'   => $preview['total'],
            'affiliate_id'   => 0,
            'commission'     => 0,
            'marketing_id'   => 0,
            'tracking'       => '',
            'language_id'    => (int) ($this->config->get('config_language_id') ?: 1),
            'currency_id'    => (int) ($this->config->get('config_currency_id') ?: 1),
            'currency_code'  => $preview['currency'],
            'currency_value' => 1.0,
            'ip'             => $this->clientIp,
            'forwarded_ip'   => '',
            'user_agent'     => 'Dowaba-AI-Plugin/0.1.0',
            'accept_language'=> 'tr-TR',

            'products' => $orderProducts,
            'vouchers' => [],
            'totals'   => $orderTotals,
        ];

        $orderId = $this->model_checkout_order->addOrder($data);

        // Order status: Pending (1)
        $this->model_checkout_order->addHistory($orderId, 1, 'Sipariş Dowaba AI ile oluşturuldu — ödeme bekleniyor');

        return (int) $orderId;
    }

    /**
     * Etkili fiyat (special > 0 ve < normal → special; aksi normal).
     */
    private function effectivePrice(array $product): float {
        $price = (float) ($product['price'] ?? 0);
        $special = (float) ($product['special'] ?? 0);
        return ($special > 0 && $special < $price) ? $special : $price;
    }

    // ============================================================ helpers

    /**
     * Auth + scope guard.
     *
     * Sırasıyla:
     *   1. Auth::verify — bearer + IP whitelist + module status
     *   2. ScopeGuard::check — read/write
     *
     * Fail durumunda respond() çağrılır (audit log dahil) ve false döner.
     * Success durumunda $this->clientIp set edilir, true döner.
     *
     * @return bool true if request authorized
     */
    private function guard(string $requiredScope): bool {
        // 1) Auth (module + bearer + ip whitelist)
        $auth = Auth::verify($this->registry);
        $this->clientIp = $auth['client_ip'];

        if (!$auth['success']) {
            $this->respond($auth['status'], ['error' => $auth['error']]);
            return false;
        }

        // 2) Scope guard
        $scope = ScopeGuard::check($this->registry, $requiredScope);
        if (!$scope['allowed']) {
            $this->respond($scope['status'], ['error' => $scope['error']]);
            return false;
        }

        return true;
    }

    private function readJsonBody(): array {
        // v0.1.2 fix: Dowaba HttpHandler Laravel Http facade kullanıyor; bazı
        // versiyonlarda content-type application/json değil application/x-www-form-urlencoded
        // gönderiyor → json_decode boş array döner. Fallback: $_POST + raw decode.
        $raw = file_get_contents('php://input') ?: '';

        // 1. Önce JSON dene
        if ($raw !== '') {
            $data = json_decode($raw, true);
            if (is_array($data) && !empty($data)) return $data;
        }

        // 2. Form-encoded fallback ($_POST OC4 request'inde de var)
        if (!empty($this->request->post)) return $this->request->post;

        // 3. Raw form-encoded parse fallback
        if ($raw !== '') {
            parse_str($raw, $parsed);
            if (!empty($parsed)) return $parsed;
        }

        return [];
    }

    private function respond(int $status, array $payload): void {
        $duration = (int) round((microtime(true) - $this->startTime) * 1000);

        // Audit log — her response (auth fail dahil) loglanır
        // clientIp henüz set edilmemişse (Auth verify'dan önce respond çağrıldıysa) REMOTE_ADDR fallback
        $ip = $this->clientIp !== '0.0.0.0'
            ? $this->clientIp
            : (string) ($this->request->server['REMOTE_ADDR'] ?? '0.0.0.0');

        AuditLogger::write(
            $this->registry,
            $this->currentSlug ?: 'unknown',
            $ip,
            $status,
            $duration,
            $status >= 400 ? (string) ($payload['error'] ?? null) : null
        );

        // v0.1.1 fix: HTTP status code'u 3 mekanizmayla set ediyoruz, OC4'ün
        // headers_sent() guard'ını ve replace timing'ini atlatmak için:
        //   1. http_response_code() — PHP native, headers_sent() check geçer
        //   2. header('HTTP/1.1 X Y', true, $status) — direkt headers'a yaz
        //   3. response->addHeader('HTTP/1.1 X Y') — OC4 buffer'ına da koy (output() sırasında)
        // Sebebi: OC4 framework headers_sent()'ten ÖNCE bazı header'lar gönderiyor,
        // sadece response->addHeader yetersiz kalabiliyor.
        $statusText = self::HTTP_STATUS_TEXTS[$status] ?? 'OK';
        http_response_code($status);
        if (!headers_sent()) {
            header('HTTP/1.1 ' . $status . ' ' . $statusText, true, $status);
        }
        $this->response->addHeader('HTTP/1.1 ' . $status . ' ' . $statusText);
        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('X-Dowaba-Duration: ' . $duration);
        $this->response->setOutput(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** HTTP status reason phrases — RFC 7231 + 4 ekstra (410, 422, 501, 503). */
    private const HTTP_STATUS_TEXTS = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        409 => 'Conflict',
        410 => 'Gone',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        503 => 'Service Unavailable',
    ];

    private function shapeProduct(array $p): array {
        $price = (float) ($p['price'] ?? 0);
        $special = (float) ($p['special'] ?? 0);
        $finalPrice = ($special > 0 && $special < $price) ? $special : $price;

        $baseUrl = rtrim((string)($this->config->get('config_url') ?: ''), '/');
        $imagePath = $p['image'] ?? null;
        $thumbUrl = null;
        $imageUrl = null;
        if (!empty($imagePath)) {
            try {
                $this->load->model('tool/image');
                $thumbUrl = $this->model_tool_image->resize($imagePath, 200, 200);
                $imageUrl = $this->model_tool_image->resize($imagePath, 600, 600);
            } catch (\Throwable $e) {
                $thumbUrl = $baseUrl . '/image/' . ltrim($imagePath, '/');
                $imageUrl = $thumbUrl;
            }
        }

        return [
            'product_id' => (int) ($p['product_id'] ?? 0),
            // HTML entity decode (&quot;, &amp; vs.) — AI clean string okusun
            'name'       => html_entity_decode((string) ($p['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'model'      => $p['model']        ?? null,   // OpenCart "Model" — ürün kodu
            'sku'        => $p['sku']          ?? null,   // OpenCart "SKU" — barkod / harici kod
            'price'      => $finalPrice,
            'original_price' => $special > 0 ? $price : null,
            'currency'   => $this->config->get('config_currency') ?: 'TRY',
            'stock'      => (int) ($p['quantity'] ?? 0),
            'in_stock'   => ((int) ($p['quantity'] ?? 0)) > 0,
            'url'        => $baseUrl . '/index.php?route=product/product&product_id=' . (int) ($p['product_id'] ?? 0),
            'thumb'      => $thumbUrl,   // 200x200 önizleme
            'image'      => $imageUrl,   // 600x600 büyük görsel
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

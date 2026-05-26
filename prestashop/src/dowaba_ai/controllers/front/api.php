<?php
/**
 * Dowaba AI Integration for PrestaShop — REST API Endpoint
 *
 * Front controller that handles all AI function calls dispatched by Dowaba SaaS.
 * URL: /index.php?fc=module&module=dowaba_ai&controller=api&action={products|product|...}
 * Class: DowabaAiApiModuleFrontController
 *
 * Dispatches 10 actions (products, product, compare, stock, categories, order,
 * customer_lookup, cart_recover, order_preview, order_confirm).
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dowaba_ai/classes/Auth.php';
require_once _PS_MODULE_DIR_ . 'dowaba_ai/classes/ScopeGuard.php';
require_once _PS_MODULE_DIR_ . 'dowaba_ai/classes/AuditLogger.php';
require_once _PS_MODULE_DIR_ . 'dowaba_ai/classes/OrderPreview.php';

class DowabaAiApiModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ssl = false;

    private $startTime = 0.0;
    private $currentSlug = '';
    private $clientIp = '0.0.0.0';

    public function initContent()
    {
        $this->startTime = microtime(true);
        $action = Tools::getValue('action', '');

        $dispatch = [
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

        if (!isset($dispatch[$action])) {
            $this->currentSlug = 'unknown';
            $this->respond(400, ['error' => 'Invalid or missing action parameter', 'valid_actions' => array_keys($dispatch)]);
            return;
        }

        $method = $dispatch[$action];
        $this->$method();
    }

    // ============================================================ READ

    public function products()
    {
        $this->currentSlug = 'psm_product_search';
        if (!$this->guard('read')) {
            return;
        }

        $query = trim((string) Tools::getValue('q', ''));
        $limit = max(1, min(50, (int) Tools::getValue('limit', 10)));
        if ($query === '') {
            $this->respond(400, ['error' => 'q parameter required']);
            return;
        }

        $id_lang = (int) Context::getContext()->language->id;
        $products = Product::searchByName($id_lang, $query, null, false, $limit);

        $data = [];
        foreach (($products ?: []) as $p) {
            $data[] = $this->shapeProductRow($p, $id_lang);
        }
        $this->respond(200, ['data' => $data, 'count' => count($data), 'query' => $query]);
    }

    public function product()
    {
        $this->currentSlug = 'psm_product_detail';
        if (!$this->guard('read')) {
            return;
        }

        $id = (int) Tools::getValue('id', 0);
        if ($id <= 0) {
            $this->respond(400, ['error' => 'id parameter required']);
            return;
        }

        $product = new Product($id, true, Context::getContext()->language->id);
        if (!Validate::isLoadedObject($product)) {
            $this->respond(404, ['error' => 'product not found', 'product_id' => $id]);
            return;
        }

        $features = [];
        foreach ($product->getFrontFeatures(Context::getContext()->language->id) as $f) {
            $features[$f['name']] = $f['value'];
        }

        $this->respond(200, [
            'data' => array_merge(
                $this->shapeProductFull($product),
                [
                    'description' => strip_tags($product->description ?: ''),
                    'short'       => strip_tags($product->description_short ?: ''),
                    'sku'         => $product->reference,
                    'weight'      => (float) $product->weight,
                    'attributes'  => $features,
                ]
            ),
        ]);
    }

    public function compare()
    {
        $this->currentSlug = 'psm_product_compare';
        if (!$this->guard('read')) {
            return;
        }

        $ids_raw = Tools::getValue('ids', '');
        $ids = is_array($ids_raw) ? $ids_raw : explode(',', (string) $ids_raw);
        $ids = array_filter(array_map('intval', $ids), fn ($id) => $id > 0);
        $ids = array_values(array_unique($ids));

        if (count($ids) < 2 || count($ids) > 3) {
            $this->respond(400, ['error' => 'ids must contain 2 or 3 unique product IDs', 'received' => $ids]);
            return;
        }

        $id_lang = (int) Context::getContext()->language->id;
        $products = [];
        $all_attrs = [];

        foreach ($ids as $pid) {
            $p = new Product($pid, true, $id_lang);
            if (!Validate::isLoadedObject($p)) {
                continue;
            }

            $features = [];
            foreach ($p->getFrontFeatures($id_lang) as $f) {
                $features[$f['name']] = $f['value'];
                $all_attrs[$f['name']] ??= [];
                $all_attrs[$f['name']][$pid] = $f['value'];
            }
            $products[] = array_merge($this->shapeProductFull($p), ['attributes' => $features]);
        }

        if (count($products) < 2) {
            $this->respond(404, ['error' => 'not enough valid products', 'received_ids' => $ids]);
            return;
        }

        $common = [];
        $diff = [];
        foreach ($all_attrs as $name => $values) {
            if (count($values) === count($products) && count(array_unique($values)) === 1) {
                $common[$name] = reset($values);
            } else {
                $diff[$name] = $values;
            }
        }

        $this->respond(200, ['data' => [
            'products' => $products,
            'common_attributes' => $common,
            'unique_differences' => $diff,
            'price_range' => [
                'min' => min(array_column($products, 'price')),
                'max' => max(array_column($products, 'price')),
            ],
        ]]);
    }

    public function stock()
    {
        $this->currentSlug = 'psm_stock_check';
        if (!$this->guard('read')) {
            return;
        }

        $pid = (int) Tools::getValue('product_id', 0);
        $sku = trim((string) Tools::getValue('sku', ''));

        $product = null;
        if ($pid > 0) {
            $product = new Product($pid, true);
        } elseif ($sku !== '') {
            $sku_id = (int) Product::getIdByReference($sku);
            if ($sku_id > 0) {
                $product = new Product($sku_id, true);
            }
        }
        if (!$product || !Validate::isLoadedObject($product)) {
            $this->respond(404, ['error' => 'product not found']);
            return;
        }

        $stock = (int) StockAvailable::getQuantityAvailableByProduct((int) $product->id);
        $this->respond(200, ['data' => [
            'product_id' => (int) $product->id,
            'name'       => $product->name,
            'sku'        => $product->reference,
            'stock'      => $stock,
            'in_stock'   => $stock > 0,
            'eta_days'   => $stock > 0 ? 0 : null,
        ]]);
    }

    public function categories()
    {
        $this->currentSlug = 'psm_category_list';
        if (!$this->guard('read')) {
            return;
        }

        $parent = max(0, (int) Tools::getValue('parent_id', 0));
        $id_lang = (int) Context::getContext()->language->id;
        $rows = Db::getInstance()->executeS(
            "SELECT c.id_category, cl.name, c.id_parent
             FROM `" . _DB_PREFIX_ . "category` c
             INNER JOIN `" . _DB_PREFIX_ . "category_lang` cl ON cl.id_category = c.id_category AND cl.id_lang = " . $id_lang . "
             WHERE c.id_parent = " . (int) $parent . " AND c.active = 1
             ORDER BY c.position ASC"
        ) ?: [];

        $data = array_map(fn ($r) => [
            'category_id' => (int) $r['id_category'],
            'name'        => $r['name'],
            'parent_id'   => (int) $r['id_parent'],
        ], $rows);

        $this->respond(200, ['data' => $data, 'count' => count($data), 'parent_id' => $parent]);
    }

    public function order()
    {
        $this->currentSlug = 'psm_order_status';
        if (!$this->guard('read')) {
            return;
        }

        $id = (int) Tools::getValue('id', 0);
        $email = strtolower(trim((string) Tools::getValue('email', '')));
        if ($id <= 0 || $email === '') {
            $this->respond(400, ['error' => 'id and email required']);
            return;
        }

        $order = new Order($id);
        if (!Validate::isLoadedObject($order)) {
            $this->respond(404, ['error' => 'order not found']);
            return;
        }
        $customer = new Customer((int) $order->id_customer);
        if (strtolower((string) $customer->email) !== $email) {
            $this->respond(404, ['error' => 'order not found']);
            return;
        }

        $this->respond(200, ['data' => [
            'order_id'    => (int) $order->id,
            'status'      => (int) $order->current_state,
            'total'       => (float) $order->total_paid,
            'currency'    => $this->context->currency->iso_code,
            'created_at'  => $order->date_add,
            'modified_at' => $order->date_upd,
        ]]);
    }

    public function customerLookup()
    {
        $this->currentSlug = 'psm_customer_lookup';
        if (!$this->guard('read')) {
            return;
        }

        $phone = trim((string) Tools::getValue('phone', ''));
        $email = strtolower(trim((string) Tools::getValue('email', '')));
        if ($phone === '' && $email === '') {
            $this->respond(400, ['error' => 'phone or email required']);
            return;
        }

        $customer = null;
        if ($email !== '') {
            $row = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "customer` WHERE LOWER(email) = '" . pSQL($email) . "' LIMIT 1");
            if ($row) {
                $customer = new Customer((int) $row['id_customer']);
            }
        }
        if ((!$customer || !Validate::isLoadedObject($customer)) && $phone !== '') {
            $norm = preg_replace('/\D+/', '', $phone);
            $row = Db::getInstance()->getRow(
                "SELECT c.id_customer FROM `" . _DB_PREFIX_ . "customer` c
                 INNER JOIN `" . _DB_PREFIX_ . "address` a ON a.id_customer = c.id_customer
                 WHERE REPLACE(REPLACE(REPLACE(a.phone, ' ', ''), '-', ''), '+', '') = '" . pSQL($norm) . "'
                 LIMIT 1"
            );
            if ($row) {
                $customer = new Customer((int) $row['id_customer']);
            }
        }

        if (!$customer || !Validate::isLoadedObject($customer)) {
            $this->respond(404, ['error' => 'customer not found']);
            return;
        }

        $orders = Db::getInstance()->executeS(
            "SELECT id_order, total_paid, date_add FROM `" . _DB_PREFIX_ . "orders` WHERE id_customer = " . (int) $customer->id . " ORDER BY id_order DESC LIMIT 5"
        ) ?: [];

        $this->respond(200, ['data' => [
            'customer_id' => (int) $customer->id,
            'name'        => trim($customer->firstname . ' ' . $customer->lastname),
            'email'       => $customer->email,
            'created_at'  => $customer->date_add,
            'recent_orders' => array_map(fn ($o) => [
                'order_id' => (int) $o['id_order'],
                'total'    => (float) $o['total_paid'],
                'date'     => $o['date_add'],
            ], $orders),
        ]]);
    }

    public function cartRecover()
    {
        $this->currentSlug = 'psm_cart_recover';
        if (!$this->guard('read')) {
            return;
        }

        $body = $this->readJsonBody();
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $cid = (int) ($body['customer_id'] ?? 0);
        if ($email === '' && $cid <= 0) {
            $this->respond(400, ['error' => 'email or customer_id required']);
            return;
        }

        $token = bin2hex(random_bytes(16));
        $link = $this->context->link->getPageLink('my-account', true) . '?dwb_recover=' . $token;
        $this->respond(200, ['data' => ['recover_link' => $link, 'expires_in' => 86400]]);
    }

    // ============================================================ WRITE

    public function orderPreview()
    {
        $this->currentSlug = 'psm_order_preview';
        if (!$this->guard('write')) {
            return;
        }

        $body = $this->readJsonBody();
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];

        if (empty($items)) {
            $this->respond(400, ['error' => 'items array required']);
            return;
        }
        if (count($items) > 50) {
            $this->respond(400, ['error' => 'too many items (max 50)']);
            return;
        }

        $phone = trim((string) ($customer['phone'] ?? ''));
        $email = strtolower(trim((string) ($customer['email'] ?? '')));
        if ($phone === '' && $email === '') {
            $this->respond(400, ['error' => 'customer.phone or customer.email required']);
            return;
        }

        $resolved = [];
        $subtotal = 0.0;
        $stock_issues = [];
        foreach ($items as $idx => $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if ($pid <= 0) {
                $this->respond(400, ['error' => "items[$idx].product_id required"]);
                return;
            }

            $product = new Product($pid, true);
            if (!Validate::isLoadedObject($product)) {
                $this->respond(404, ['error' => 'product not found', 'product_id' => $pid]);
                return;
            }

            $stock = (int) StockAvailable::getQuantityAvailableByProduct((int) $product->id);
            if ($stock < $qty) {
                $stock_issues[] = [
                    'product_id' => $pid, 'name' => $product->name,
                    'requested_qty' => $qty, 'available_stock' => $stock,
                ];
            }
            $unit_price = (float) $product->price;
            $line_total = $unit_price * $qty;
            $subtotal += $line_total;

            $resolved[] = [
                'product_id' => $pid, 'name' => $product->name, 'sku' => $product->reference,
                'quantity' => $qty, 'unit_price' => $unit_price, 'line_total' => $line_total, 'stock' => $stock,
            ];
        }

        if (!empty($stock_issues)) {
            $this->respond(409, ['error' => 'insufficient stock', 'stock_issues' => $stock_issues]);
            return;
        }

        $shipping = $subtotal >= 1000 ? 0.0 : 49.0;
        $total = $subtotal + $shipping;

        $preview_id = DowabaOrderPreview::generateId();
        $payload = [
            'items' => $resolved,
            'customer' => [
                'phone' => $phone, 'email' => $email,
                'name' => trim((string) ($customer['name'] ?? '')),
                'address' => trim((string) ($customer['address'] ?? '')),
                'city' => trim((string) ($customer['city'] ?? '')),
            ],
            'subtotal' => round($subtotal, 2),
            'shipping_cost' => $shipping,
            'total' => round($total, 2),
            'currency' => $this->context->currency->iso_code,
        ];
        DowabaOrderPreview::store($preview_id, $payload);

        $this->respond(200, [
            'preview_id' => $preview_id,
            'expires_at' => date('c', time() + DowabaOrderPreview::TTL_SECONDS),
            'expires_in_seconds' => DowabaOrderPreview::TTL_SECONDS,
            'summary' => $payload,
            'note' => 'AI\'ya: müşteriye özet göster, onay sonrası psm_order_confirm.',
        ]);
    }

    public function orderConfirm()
    {
        $this->currentSlug = 'psm_order_confirm';
        if (!$this->guard('write')) {
            return;
        }

        $body = $this->readJsonBody();
        $preview_id = trim((string) ($body['preview_id'] ?? ''));
        $confirmed = filter_var($body['confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!DowabaOrderPreview::isValidId($preview_id)) {
            $this->respond(400, ['error' => 'invalid preview_id format']);
            return;
        }
        if (!$confirmed) {
            $this->respond(400, ['error' => 'confirmed must be true']);
            return;
        }

        $preview = DowabaOrderPreview::consume($preview_id);
        if ($preview === null) {
            $this->respond(410, [
                'error' => 'preview_id expired or already consumed',
                'note' => 'AI\'ya: yeni preview_id için psm_order_preview çağır.',
            ]);
            return;
        }

        try {
            $order_id = $this->createOrderFromPreview($preview);
        } catch (\Throwable $e) {
            $this->respond(500, ['error' => 'order creation failed', 'detail' => $e->getMessage()]);
            return;
        }

        $this->respond(200, [
            'data' => [
                'order_id'    => $order_id,
                'status'      => 'pending',
                'payment_url' => Context::getContext()->link->getPageLink('order-confirmation', true, null, ['id_order' => $order_id]),
                'total'       => $preview['total'],
                'currency'    => $preview['currency'],
            ],
            'note' => 'AI\'ya: müşteriye "Siparişin oluştu #' . $order_id . '" bildir.',
        ]);
    }

    // ============================================================ helpers

    private function createOrderFromPreview(array $preview): int
    {
        $customer = $preview['customer'];
        $name_parts = preg_split('/\s+/', trim($customer['name']), 2);
        $first = $name_parts[0] ?? 'Guest';
        $last  = $name_parts[1] ?? 'Customer';

        // Customer (var mı? yoksa guest oluştur)
        $customer_id = 0;
        if ($customer['email']) {
            $row = Db::getInstance()->getRow("SELECT id_customer FROM `" . _DB_PREFIX_ . "customer` WHERE LOWER(email) = '" . pSQL(strtolower($customer['email'])) . "' LIMIT 1");
            if ($row) {
                $customer_id = (int) $row['id_customer'];
            }
        }

        if (!$customer_id) {
            $new = new Customer();
            $new->firstname = $first;
            $new->lastname = $last;
            $new->email = $customer['email'] ?: ('guest+' . uniqid() . '@dowaba.local');
            $new->passwd = Tools::hash(bin2hex(random_bytes(16)));
            $new->is_guest = 1;
            $new->newsletter = 0;
            $new->active = 1;
            if (!$new->add()) {
                throw new \RuntimeException('customer create failed');
            }
            $customer_id = (int) $new->id;
        }

        // Address
        $address = new Address();
        $address->id_customer = $customer_id;
        $address->id_country = (int) Country::getByIso('TR') ?: (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $address->firstname = $first;
        $address->lastname = $last;
        $address->address1 = $customer['address'] ?: 'No address';
        $address->city = $customer['city'] ?: 'Unknown';
        $address->postcode = '00000';
        $address->alias = 'DoWaba AI';
        $address->phone = $customer['phone'];
        if (!$address->add()) {
            throw new \RuntimeException('address create failed');
        }

        // Cart
        $cart = new Cart();
        $cart->id_customer = $customer_id;
        $cart->id_address_delivery = (int) $address->id;
        $cart->id_address_invoice = (int) $address->id;
        $cart->id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $cart->id_carrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        $cart->id_shop = (int) Context::getContext()->shop->id;
        $cart->id_shop_group = (int) Context::getContext()->shop->id_shop_group;
        $cart->secure_key = md5(uniqid(bin2hex(random_bytes(8)), true));
        if (!$cart->add()) {
            throw new \RuntimeException('cart create failed');
        }
        foreach ($preview['items'] as $item) {
            $cart->updateQty((int) $item['quantity'], (int) $item['product_id']);
        }
        $cart->update();

        // PaymentModule order create
        $paymentModule = Module::getInstanceByName('ps_cashondelivery') ?: new PaymentModule();
        $paymentModule->validateOrder(
            (int) $cart->id,
            (int) Configuration::get('PS_OS_PREPARATION'),
            (float) $preview['total'],
            'Cash on Delivery (DoWaba AI)',
            'Sipariş DoWaba AI üzerinden oluşturuldu (preview_id: ' . ($preview['_preview_id'] ?? '') . ')',
            [],
            (int) Context::getContext()->currency->id,
            false,
            $cart->secure_key
        );

        return (int) $paymentModule->currentOrder;
    }

    private function shapeProductRow(array $row, int $id_lang): array
    {
        $id = (int) $row['id_product'];
        $product = new Product($id, false, $id_lang);
        return [
            'product_id' => $id,
            'name'       => $row['name'] ?? $product->name,
            'price'      => (float) Product::getPriceStatic($id),
            'currency'   => Context::getContext()->currency->iso_code,
            'stock'      => (int) StockAvailable::getQuantityAvailableByProduct($id),
            'in_stock'   => StockAvailable::getQuantityAvailableByProduct($id) > 0,
            'url'        => Context::getContext()->link->getProductLink($product),
            'thumb'      => Context::getContext()->link->getImageLink($product->link_rewrite, Product::getCover($id)['id_image'] ?? 0, 'home_default'),
        ];
    }

    private function shapeProductFull(Product $p): array
    {
        return [
            'product_id' => (int) $p->id,
            'name'       => $p->name,
            'price'      => (float) Product::getPriceStatic((int) $p->id),
            'currency'   => Context::getContext()->currency->iso_code,
            'stock'      => (int) StockAvailable::getQuantityAvailableByProduct((int) $p->id),
            'in_stock'   => StockAvailable::getQuantityAvailableByProduct((int) $p->id) > 0,
            'url'        => Context::getContext()->link->getProductLink($p),
            'thumb'      => Context::getContext()->link->getImageLink($p->link_rewrite, Product::getCover((int) $p->id)['id_image'] ?? 0, 'home_default'),
        ];
    }

    private function guard(string $scope): bool
    {
        $auth = DowabaAuth::verify();
        $this->clientIp = $auth['client_ip'];
        if (!$auth['success']) {
            $this->respond($auth['status'], ['error' => $auth['error']]);
            return false;
        }
        $sg = DowabaScopeGuard::check($scope);
        if (!$sg['allowed']) {
            $this->respond($sg['status'], ['error' => $sg['error']]);
            return false;
        }
        return true;
    }

    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw !== '') {
            $data = json_decode($raw, true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }
        if (!empty($_POST)) {
            return $_POST;
        }
        if ($raw !== '') {
            parse_str($raw, $parsed);
            if (!empty($parsed)) {
                return $parsed;
            }
        }
        return [];
    }

    private function respond(int $status, array $payload): void
    {
        $duration = (int) round((microtime(true) - $this->startTime) * 1000);

        DowabaAuditLogger::write(
            $this->currentSlug ?: 'unknown',
            $this->clientIp,
            $status,
            $duration,
            $status >= 400 ? (string) ($payload['error'] ?? null) : null
        );

        $status_texts = [
            200 => 'OK', 400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
            404 => 'Not Found', 409 => 'Conflict', 410 => 'Gone', 500 => 'Internal Server Error', 503 => 'Service Unavailable',
        ];
        $text = $status_texts[$status] ?? 'OK';

        http_response_code($status);
        if (!headers_sent()) {
            header("HTTP/1.1 $status $text", true, $status);
        }
        header('Content-Type: application/json; charset=utf-8');
        header('X-Dowaba-Duration: ' . $duration);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

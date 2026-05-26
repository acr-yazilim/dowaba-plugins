<?php
/**
 * Dowaba AI Integration for PrestaShop — Manifest Endpoint.
 *
 * Serves Bundle Import manifest JSON (10 AI function definitions).
 * URL: /index.php?fc=module&module=dowaba_ai&controller=manifest
 * Class name follows PrestaShop convention:
 *   filename "manifest" -> class "DowabaAiManifestModuleFrontController"
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'dowaba_ai/dowaba_ai.php';

class DowabaAiManifestModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ssl = false;

    public function initContent()
    {
        parent::initContent();

        $base = $this->resolveBaseUrl();
        $store_name = Configuration::get('PS_SHOP_NAME') ?: 'PrestaShop Store';

        $manifest = [
            'schema_version' => '1.0',
            'name' => 'PrestaShop — ' . $store_name,
            'plugin_version' => '0.1.0',
            'platform' => 'prestashop',
            'connection' => [
                'type' => 'http_api',
                'base_url' => $base . '/index.php',
                'auth_type' => 'bearer',
                'allowed_hosts' => [parse_url($base, PHP_URL_HOST) ?: ''],
            ],
            'functions' => [
                $this->fn(
                    'psm_product_search',
                    'Ürün ara',
                    'Ad/SKU/kategoriye göre ürün listele.',
                    'read',
                    ['type' => 'object', 'properties' => [
                        'query' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                    ], 'required' => ['query']],
                    'GET',
                    'products',
                    ['q' => '{{arg.query}}', 'limit' => '{{arg.limit}}'],
                    null,
                    5000,
                    ['data_path' => 'data', 'fields' => ['product_id', 'name', 'price', 'stock', 'url', 'thumb']]
                ),
                $this->fn(
                    'psm_product_detail',
                    'Ürün detayı',
                    'Tek ürün tam bilgi.',
                    'read',
                    ['type' => 'object', 'properties' => ['product_id' => ['type' => 'integer']], 'required' => ['product_id']],
                    'GET',
                    'product',
                    ['id' => '{{arg.product_id}}'],
                    null,
                    5000
                ),
                $this->fn(
                    'psm_product_compare',
                    'Ürün karşılaştır',
                    '2-3 ürün yan yana.',
                    'read',
                    ['type' => 'object', 'properties' => [
                        'product_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 2, 'maxItems' => 3],
                    ], 'required' => ['product_ids']],
                    'GET',
                    'compare',
                    ['ids' => '{{arg.product_ids}}'],
                    null,
                    8000
                ),
                $this->fn(
                    'psm_stock_check',
                    'Stok kontrol',
                    'Ürünün stok adedi.',
                    'read',
                    ['type' => 'object', 'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'sku' => ['type' => 'string'],
                    ]],
                    'GET',
                    'stock',
                    ['product_id' => '{{arg.product_id}}', 'sku' => '{{arg.sku}}'],
                    null,
                    3000
                ),
                $this->fn(
                    'psm_category_list',
                    'Kategori liste',
                    'Kategori ağacı.',
                    'read',
                    ['type' => 'object', 'properties' => ['parent_id' => ['type' => 'integer', 'default' => 0]]],
                    'GET',
                    'categories',
                    ['parent_id' => '{{arg.parent_id}}'],
                    null,
                    5000
                ),
                $this->fn(
                    'psm_order_status',
                    'Sipariş durumu',
                    'Email match ile.',
                    'read',
                    ['type' => 'object', 'properties' => [
                        'order_id' => ['type' => 'integer'],
                        'email' => ['type' => 'string'],
                    ], 'required' => ['order_id', 'email']],
                    'GET',
                    'order',
                    ['id' => '{{arg.order_id}}', 'email' => '{{arg.email}}'],
                    null,
                    5000
                ),
                $this->fn(
                    'psm_customer_lookup',
                    'Müşteri sorgu',
                    'Telefon/email ile.',
                    'read',
                    ['type' => 'object', 'properties' => [
                        'phone' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                    ]],
                    'GET',
                    'customer_lookup',
                    ['phone' => '{{arg.phone}}', 'email' => '{{arg.email}}'],
                    null,
                    5000
                ),
                $this->fn(
                    'psm_cart_recover',
                    'Sepet hatırlat',
                    'Re-engagement link.',
                    'read',
                    ['type' => 'object', 'properties' => [
                        'email' => ['type' => 'string'],
                        'customer_id' => ['type' => 'integer'],
                    ]],
                    'POST',
                    'cart_recover',
                    null,
                    ['email' => '{{arg.email}}', 'customer_id' => '{{arg.customer_id}}'],
                    5000
                ),
                $this->fn(
                    'psm_order_preview',
                    'Sipariş önizle',
                    'KRİTİK: müşteri onayı öncesi özet.',
                    'write',
                    // FIX 2026-05-26: Gemini JSON Schema strict — array için items, object için properties ZORUNLU.
                    // Bkz: PLUGIN_DEV_GUIDE.md §3 canlı vakası.
                    ['type' => 'object', 'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'product_id' => ['type' => 'integer', 'description' => 'PrestaShop ürün ID'],
                                    'quantity' => ['type' => 'integer', 'default' => 1],
                                ],
                                'required' => ['product_id'],
                            ],
                        ],
                        'customer' => [
                            'type' => 'object',
                            'properties' => [
                                'phone' => ['type' => 'string'],
                                'email' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'address' => ['type' => 'string'],
                                'city' => ['type' => 'string'],
                            ],
                        ],
                    ], 'required' => ['items', 'customer']],
                    'POST',
                    'order_preview',
                    null,
                    ['items' => '{{arg.items}}', 'customer' => '{{arg.customer}}'],
                    8000
                ),
                $this->fn(
                    'psm_order_confirm',
                    'Sipariş onayla',
                    'KRİTİK: müşteri "evet" sonrası.',
                    'write',
                    ['type' => 'object', 'properties' => [
                        'preview_id' => ['type' => 'string'],
                        'confirmed' => ['type' => 'boolean', 'default' => true],
                    ], 'required' => ['preview_id']],
                    'POST',
                    'order_confirm',
                    null,
                    ['preview_id' => '{{arg.preview_id}}', 'confirmed' => '{{arg.confirmed}}'],
                    10000
                ),
            ],
        ];

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: public, max-age=300');
        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    private function fn(string $slug, string $name, string $desc, string $scope, array $parameters, string $method, string $action, ?array $query_extra, ?array $body_template, int $timeout, array $response = null): array
    {
        $query = ['fc' => 'module', 'module' => 'dowaba_ai', 'controller' => 'api', 'action' => $action];
        if ($query_extra) {
            foreach ($query_extra as $k => $v) {
                $query[$k] = $v;
            }
        }

        $config = [
            'method' => $method,
            'url_template' => '{{connection.base_url}}',
            'query_template' => $query,
            'timeout_ms' => $timeout,
        ];
        if (null !== $body_template) {
            $config['body_template'] = $body_template;
        }
        if (null !== $response) {
            $config['response'] = $response;
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'description' => $desc,
            'auto_activate' => true,
            'scope' => $scope,
            'parameters' => $parameters,
            'http_config' => $config,
        ];
    }

    private function resolveBaseUrl(): string
    {
        $override = trim((string) Configuration::get('DOWABA_AI_MANIFEST_BASE_URL'));
        if ('' !== $override) {
            return rtrim($override, '/');
        }

        $fwd_host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        $fwd_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if ('' !== $fwd_host) {
            return ($fwd_proto ?: 'https') . '://' . $fwd_host;
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ('' !== $host) {
            $is_https = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']) || ($_SERVER['SERVER_PORT'] ?? '') == 443;

            return ($is_https ? 'https' : 'http') . '://' . $host;
        }

        return rtrim(Tools::getShopDomainSsl(true), '/');
    }
}

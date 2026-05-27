<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

/**
 * Manifest endpoint — /wp-json/dowaba/v1/manifest
 * Dowaba paneli Bundle Import için bunu fetch eder.
 */
final class Manifest {

    public static function register_routes(): void {
        register_rest_route(DOWABA_AI_REST_NAMESPACE, '/manifest', [
            'methods'  => 'GET',
            'callback' => [self::class, 'handle'],
            'permission_callback' => '__return_true', // Public
        ]);
    }

    public static function handle(): \WP_REST_Response {
        $base = self::resolve_base_url();
        $store_name = get_bloginfo('name') ?: 'WooCommerce Store';

        $manifest = [
            'schema_version' => '1.0',
            'name'           => 'WooCommerce — ' . $store_name,
            'plugin_version' => DOWABA_AI_VERSION,
            'platform'       => 'woocommerce',
            'connection' => [
                'type'          => 'http_api',
                'base_url'      => $base . '/wp-json/' . DOWABA_AI_REST_NAMESPACE,
                'auth_type'     => 'bearer',
                'allowed_hosts' => [parse_url($base, PHP_URL_HOST) ?: ''],
            ],
            'functions' => [
                self::fn('wcm_product_search', 'Ürün ara', 'Ad/SKU/kategoriye göre ürün listele. Her ürün için kapak (cover) + küçük (thumb) görsel döner.', 'read',
                    ['type' => 'object', 'properties' => [
                        'query' => ['type' => 'string'],
                        'limit' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                    ], 'required' => ['query']],
                    'GET', '/products',
                    ['q' => '{{arg.query}}', 'limit' => '{{arg.limit}}'], null, 5000,
                    ['data_path' => 'data', 'fields' => ['product_id', 'name', 'price', 'stock', 'url', 'thumb', 'cover']]
                ),
                self::fn('wcm_product_detail', 'Ürün detayı', 'Tek ürün tam bilgi — açıklama, SKU, attribute\'lar + gallery_images (kapak + tüm galeri görselleri, thumb/medium/full URL).', 'read',
                    ['type' => 'object', 'properties' => ['product_id' => ['type' => 'integer']], 'required' => ['product_id']],
                    'GET', '/product/{{arg.product_id}}', null, null, 5000
                ),
                self::fn('wcm_product_compare', 'Ürün karşılaştır', '2-3 ürünü yan yana — fiyat, attribute farkları + her ürünün kapak görseli.', 'read',
                    ['type' => 'object', 'properties' => [
                        'product_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'minItems' => 2, 'maxItems' => 3],
                    ], 'required' => ['product_ids']],
                    'GET', '/compare', ['ids' => '{{arg.product_ids}}'], null, 8000
                ),
                self::fn('wcm_stock_check', 'Stok kontrol', 'Ürünün stok adedi.', 'read',
                    ['type' => 'object', 'properties' => [
                        'product_id' => ['type' => 'integer'],
                        'sku' => ['type' => 'string'],
                    ]],
                    'GET', '/stock', ['product_id' => '{{arg.product_id}}', 'sku' => '{{arg.sku}}'], null, 3000
                ),
                self::fn('wcm_category_list', 'Kategori liste', 'Mağaza kategori ağacı.', 'read',
                    ['type' => 'object', 'properties' => ['parent_id' => ['type' => 'integer', 'default' => 0]]],
                    'GET', '/categories', ['parent_id' => '{{arg.parent_id}}'], null, 5000
                ),
                self::fn('wcm_order_status', 'Sipariş durumu', 'Email match ile sipariş takibi.', 'read',
                    ['type' => 'object', 'properties' => [
                        'order_id' => ['type' => 'integer'],
                        'email' => ['type' => 'string'],
                    ], 'required' => ['order_id', 'email']],
                    'GET', '/order/{{arg.order_id}}', ['email' => '{{arg.email}}'], null, 5000
                ),
                self::fn('wcm_customer_lookup', 'Müşteri sorgu', 'Telefon/email ile müşteri profili.', 'read',
                    ['type' => 'object', 'properties' => [
                        'phone' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                    ]],
                    'GET', '/customer/lookup', ['phone' => '{{arg.phone}}', 'email' => '{{arg.email}}'], null, 5000
                ),
                self::fn('wcm_cart_recover', 'Sepet hatırlat', 'Terkedilmiş sepete dönüş link.', 'read',
                    ['type' => 'object', 'properties' => [
                        'email' => ['type' => 'string'],
                        'customer_id' => ['type' => 'integer'],
                    ]],
                    'POST', '/cart/recover', null,
                    ['email' => '{{arg.email}}', 'customer_id' => '{{arg.customer_id}}'], 5000
                ),
                self::fn('wcm_order_preview', 'Sipariş önizle', 'KRİTİK: sipariş yaratmadan ÖNCE özet. Müşteri onayı sonrası wcm_order_confirm.', 'write',
                    // FIX 2026-05-26: Gemini JSON Schema strict — array için items, object için properties ZORUNLU.
                    // Boş `{type: array}` ve `{type: object}` Gemini reject → tüm function listesi reddedilir →
                    // AI hiç tool call yapmaz → kullanıcıya generic fallback gider (silent failure).
                    // Bkz: PLUGIN_DEV_GUIDE.md §3 canlı vakası.
                    ['type' => 'object', 'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'product_id' => ['type' => 'integer', 'description' => 'WooCommerce ürün ID'],
                                    'quantity'   => ['type' => 'integer', 'default' => 1],
                                ],
                                'required' => ['product_id'],
                            ],
                        ],
                        'customer' => [
                            'type' => 'object',
                            'properties' => [
                                'phone'   => ['type' => 'string'],
                                'email'   => ['type' => 'string'],
                                'name'    => ['type' => 'string'],
                                'address' => ['type' => 'string'],
                                'city'    => ['type' => 'string'],
                            ],
                        ],
                    ], 'required' => ['items', 'customer']],
                    'POST', '/order/preview', null,
                    ['items' => '{{arg.items}}', 'customer' => '{{arg.customer}}'], 8000
                ),
                self::fn('wcm_order_confirm', 'Sipariş onayla', 'KRİTİK: SADECE müşteri preview\'ı onayladıktan sonra. 5dk TTL.', 'write',
                    ['type' => 'object', 'properties' => [
                        'preview_id' => ['type' => 'string'],
                        'confirmed' => ['type' => 'boolean', 'default' => true],
                    ], 'required' => ['preview_id']],
                    'POST', '/order/confirm', null,
                    ['preview_id' => '{{arg.preview_id}}', 'confirmed' => '{{arg.confirmed}}'], 10000
                ),
            ],
        ];

        $response = new \WP_REST_Response($manifest, 200);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Cache-Control', 'public, max-age=300');
        return $response;
    }

    // ---------------------------------------------------------------- helpers

    private static function fn(string $slug, string $name, string $desc, string $scope, array $parameters, string $method, string $path, ?array $query, ?array $body, int $timeout, ?array $response = null): array {
        $config = [
            'method'       => $method,
            'url_template' => '{{connection.base_url}}' . $path,
            'timeout_ms'   => $timeout,
        ];
        if ($query !== null) $config['query_template'] = $query;
        if ($body !== null)  $config['body_template']  = $body;
        if ($response !== null) $config['response'] = $response;

        return [
            'slug'          => $slug,
            'name'          => $name,
            'description'   => $desc,
            'auto_activate' => true,
            'scope'         => $scope,
            'parameters'    => $parameters,
            'http_config'   => $config,
        ];
    }

    private static function resolve_base_url(): string {
        $override = trim((string) get_option('dowaba_ai_manifest_base_url', ''));
        if ($override !== '') return rtrim($override, '/');

        $forwarded_host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        $forwarded_proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if ($forwarded_host !== '') {
            return ($forwarded_proto ?: 'https') . '://' . $forwarded_host;
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
            return ($is_https ? 'https' : 'http') . '://' . $host;
        }
        return rtrim(home_url(), '/');
    }
}

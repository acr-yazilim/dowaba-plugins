<?php
/**
 * Dowaba AI Bundle Manifest — OpenCart 3.x.
 * Route: extension/dowaba_ai/manifest
 * Class: ControllerExtensionDowabaAiManifest
 */
class ControllerExtensionDowabaAiManifest extends Controller {

    /**
     * Plugin sürümü — TEK OTORİTE.
     * 2026-05-28 BUG FIX: Önceden runtime'da DIR_SYSTEM.'../install.xml' parse edilmeye çalışılıyordu,
     * ama OCMOD installer install.xml'i kuruluma kopyalamıyor (sadece directive olarak işliyor + atıyor).
     * Sonuçta her sürümde fallback '0.2.3' dönüyordu — manifest sürüm bilgisi takılı kalıyordu.
     * Çözüm: const olarak gömüldü. build.sh sed ile install.xml'deki version'la senkron tutuyor.
     */
    const PLUGIN_VERSION = '0.2.16';

    public function index() {
        $base = $this->resolveBaseUrl();
        $apiBase = $base . '/index.php';
        $host = parse_url($base, PHP_URL_HOST) ?: '';
        $storeName = $this->config->get('config_name') ?: 'OpenCart Store';
        $pluginVersion = $this->getPluginVersion();

        $manifest = array(
            'schema_version' => '1.0',
            'name'           => 'OpenCart 3 — ' . $storeName,
            'plugin_version' => $pluginVersion,
            'platform'       => 'opencart3',
            'connection' => array(
                'type'          => 'http_api',
                'base_url'      => $apiBase,
                'auth_type'     => 'bearer',
                'allowed_hosts' => $host ? array($host) : array(),
            ),
            'functions' => array(
                $this->fn('opc_product_search', 'Ürün ara',
                    'OpenCart mağazasında ad/SKU/anahtar kelimeyle ürün listele.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'query' => array('type' => 'string'),
                        'limit' => array('type' => 'integer', 'default' => 10, 'maximum' => 50),
                    ), 'required' => array('query')),
                    'GET', 'products',
                    array('q' => '{{arg.query}}', 'limit' => '{{arg.limit}}'),
                    null, 5000,
                    array('data_path' => 'data', 'fields' => array('product_id','name','price','stock','url','thumb'))
                ),
                $this->fn('opc_product_detail', 'Ürün detayı',
                    'Belirli bir ürünün tam bilgilerini döner.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'product_id' => array('type' => 'integer'),
                    ), 'required' => array('product_id')),
                    'GET', 'product',
                    array('id' => '{{arg.product_id}}'), null, 5000
                ),
                $this->fn('opc_product_compare', 'Ürün karşılaştır',
                    '2-3 ürünü yan yana karşılaştır.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'product_ids' => array('type' => 'array', 'items' => array('type' => 'integer'), 'minItems' => 2, 'maxItems' => 3),
                    ), 'required' => array('product_ids')),
                    'GET', 'compare',
                    array('ids' => '{{arg.product_ids}}'), null, 8000
                ),
                $this->fn('opc_stock_check', 'Stok kontrolü',
                    'Bir ürünün stok adedini hızlıca döner.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'product_id' => array('type' => 'integer'),
                        'sku' => array('type' => 'string'),
                    )),
                    'GET', 'stock',
                    array('product_id' => '{{arg.product_id}}', 'sku' => '{{arg.sku}}'), null, 3000
                ),
                $this->fn('opc_category_list', 'Kategori listele',
                    'Mağazadaki kategori ağacı.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'parent_id' => array('type' => 'integer', 'default' => 0),
                    )),
                    'GET', 'categories',
                    array('parent_id' => '{{arg.parent_id}}'), null, 5000
                ),
                $this->fn('opc_order_status', 'Sipariş durumu',
                    'Sipariş durumu sorgula (email match zorunlu).',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'order_id' => array('type' => 'integer'),
                        'email' => array('type' => 'string'),
                    ), 'required' => array('order_id', 'email')),
                    'GET', 'order',
                    array('id' => '{{arg.order_id}}', 'email' => '{{arg.email}}'), null, 5000
                ),
                $this->fn('opc_customer_lookup', 'Müşteri sorgu',
                    'Telefon veya email ile müşteri profili.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'phone' => array('type' => 'string'),
                        'email' => array('type' => 'string'),
                    )),
                    'GET', 'customer_lookup',
                    array('phone' => '{{arg.phone}}', 'email' => '{{arg.email}}'), null, 5000
                ),
                $this->fn('opc_cart_recover', 'Sepet hatırlat',
                    'Terkedilmiş sepete dönüş link\'i.',
                    'read',
                    array('type' => 'object', 'properties' => array(
                        'email' => array('type' => 'string'),
                        'customer_id' => array('type' => 'integer'),
                    )),
                    'POST', 'cart_recover',
                    null, array('email' => '{{arg.email}}', 'customer_id' => '{{arg.customer_id}}'), 5000
                ),
                $this->fn('opc_order_preview', 'Sipariş önizle (onay öncesi)',
                    'KRİTİK: Sipariş oluşturmadan ÖNCE bu çağrılır. Müşteri özet onaylayınca opc_order_confirm.',
                    'write',
                    // FIX 2026-05-26: Gemini JSON Schema strict — `array` ve `object` tipler için
                    // `items`/`properties` zorunlu, yoksa "function declaration invalid" ile reddediliyor
                    // ve AI tüm function listesini kaybedip fallback'e düşüyor.
                    array('type' => 'object', 'properties' => array(
                        'items' => array(
                            'type' => 'array',
                            'items' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'product_id' => array('type' => 'integer', 'description' => 'OpenCart ürün ID'),
                                    'quantity' => array('type' => 'integer', 'default' => 1),
                                ),
                                'required' => array('product_id'),
                            ),
                        ),
                        'customer' => array(
                            'type' => 'object',
                            'properties' => array(
                                'phone' => array('type' => 'string'),
                                'email' => array('type' => 'string'),
                                'name' => array('type' => 'string'),
                                'address' => array('type' => 'string'),
                                'city' => array('type' => 'string'),
                            ),
                        ),
                    ), 'required' => array('items', 'customer')),
                    'POST', 'order_preview',
                    null, array('items' => '{{arg.items}}', 'customer' => '{{arg.customer}}'), 8000
                ),
                $this->fn('opc_order_confirm', 'Siparişi onayla',
                    'KRİTİK: SADECE opc_order_preview yanıtı müşteriye gösterilip onay alındıktan sonra çağrılır. 5dk TTL.',
                    'write',
                    array('type' => 'object', 'properties' => array(
                        'preview_id' => array('type' => 'string'),
                        'confirmed' => array('type' => 'boolean', 'default' => true),
                    ), 'required' => array('preview_id')),
                    'POST', 'order_confirm',
                    null, array('preview_id' => '{{arg.preview_id}}', 'confirmed' => '{{arg.confirmed}}'), 10000
                ),
            ),
        );

        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Access-Control-Allow-Origin: *');
        $this->response->addHeader('Cache-Control: public, max-age=300');
        $this->response->setOutput(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ---------------------------------------------------------------- helpers

    private function fn($slug, $name, $description, $scope, $parameters, $method, $action, $queryExtra, $bodyTemplate, $timeoutMs, $response = null) {
        $query = array('route' => 'extension/dowaba_ai/api', 'action' => $action);
        if ($queryExtra) {
            foreach ($queryExtra as $k => $v) $query[$k] = $v;
        }

        $config = array(
            'method'         => $method,
            'url_template'   => '{{connection.base_url}}',
            'query_template' => $query,
            'timeout_ms'     => $timeoutMs,
        );
        if ($bodyTemplate !== null) $config['body_template'] = $bodyTemplate;
        if ($response !== null) $config['response'] = $response;

        return array(
            'slug'          => $slug,
            'name'          => $name,
            'description'   => $description,
            'auto_activate' => true,
            'scope'         => $scope,
            'parameters'    => $parameters,
            'http_config'   => $config,
        );
    }

    private function resolveBaseUrl() {
        $override = trim((string)$this->config->get('module_dowaba_ai_manifest_base_url'));
        if ($override !== '') return rtrim($override, '/');

        $server = $this->request->server;
        $fwHost = isset($server['HTTP_X_FORWARDED_HOST']) ? $server['HTTP_X_FORWARDED_HOST'] : '';
        $fwProto = isset($server['HTTP_X_FORWARDED_PROTO']) ? $server['HTTP_X_FORWARDED_PROTO'] : '';
        if ($fwHost !== '') {
            return ($fwProto !== '' ? $fwProto : 'https') . '://' . $fwHost;
        }
        $host = isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : '';
        if ($host !== '') {
            $https = isset($server['HTTPS']) ? $server['HTTPS'] : '';
            $isHttps = ($https && $https !== 'off') || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443);
            return ($isHttps ? 'https' : 'http') . '://' . $host;
        }
        return rtrim((string)(defined('HTTP_CATALOG') ? HTTP_CATALOG : $this->config->get('config_url')), '/');
    }

    private function getPluginVersion() {
        // 2026-05-28: runtime install.xml parse'ı KALDIRILDI — OCMOD installer install.xml'i
        // kuruluma kopyalamıyor (sadece directive). const tek otorite, build.sh ile sync.
        return self::PLUGIN_VERSION;
    }
}

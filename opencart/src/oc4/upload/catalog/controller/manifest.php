<?php
namespace Opencart\Catalog\Controller\Extension\DowabaAi;

/**
 * Dowaba AI Bundle Manifest — public JSON endpoint.
 *
 * Dowaba paneli bunu fetch eder ve içindeki function tanımlarını
 * `SiteConnection` + `FunctionDefinition` olarak kayıt eder.
 *
 * Route: extension/dowaba_ai/manifest
 * URL:   https://{store}/index.php?route=extension/dowaba_ai/manifest
 *
 * NOT: Public endpoint — auth YOK. Manifest'in kendisi public bilgi
 * (sadece function tanımları, URL'ler ve param schemas). Gerçek auth
 * her function call'da `Authorization: Bearer ...` header'ında yapılır
 * (Auth.php — Faz 2).
 */
class Manifest extends \Opencart\System\Engine\Controller {

    /**
     * Plugin sürümü — TEK OTORİTE.
     * 2026-05-28 BUG FIX: Önceden DIR_OPENCART.'extension/dowaba_ai/install.json' parse'a güveniyorduk;
     * OC4 marketplace installer extract path'i versiyonlar arası tutarsız (extension/<code>/upload/ vs.
     * extension/<code>/ farklı). Sonuçta '0.0.0-dev' fallback dönebiliyordu.
     * Çözüm: const olarak gömüldü. build.sh sed ile install.json'daki version'la senkron tutuyor.
     */
    const PLUGIN_VERSION = '0.2.15';

    public function index(): void {
        $this->load->language('extension/dowaba_ai/module/dowaba');

        // v0.1.1 fix: Dinamik base URL — request host/scheme'inden inşa edilir.
        // Eskiden $config->get('config_url') statik OC kurulum URL'i dönüyordu.
        // Cloudflare tunnel / ngrok / reverse proxy ortamlarında manifest base_url'inin
        // dış URL ile aynı olması ŞART — yoksa Dowaba manifest çekemez veya API'yi
        // yanlış host'a çağırır (örn manifest'i tunnel'dan çekip API'yi localhost'a
        // atmaya çalışır → fail).
        // Override için (rakip CDN'ler ardında): module_dowaba_ai_manifest_base_url setting.
        $base = $this->resolveBaseUrl();
        // v0.1.2 fix: Dowaba HttpHandler URL parse + query string DROP davranışı.
        // base_url'a `?route=...` koyduğumuzda HttpHandler bu query'i silip kendi
        // query_template'ini yazıyor → route param kaybolur. Çözüm: base_url SADECE
        // schema+host+path, route query_template'e ayrı parametre olarak konur.
        $apiBase = $base . '/index.php';
        $host = parse_url($base, PHP_URL_HOST) ?: '';
        $storeName = $this->config->get('config_name') ?: 'OpenCart Store';
        $pluginVersion = $this->getPluginVersion();

        $manifest = [
            'schema_version' => '1.0',
            'name'           => 'OpenCart — ' . $storeName,
            'plugin_version' => $pluginVersion,
            'platform'       => 'opencart',
            'connection' => [
                'type'          => 'http_api',
                'base_url'      => $apiBase,
                'auth_type'     => 'bearer',
                'allowed_hosts' => $host ? [$host] : [],
            ],
            'functions' => [
                $this->fnProductSearch(),
                $this->fnProductDetail(),
                $this->fnProductCompare(),
                $this->fnStockCheck(),
                $this->fnCategoryList(),
                $this->fnOrderStatus(),
                $this->fnCustomerLookup(),
                $this->fnCartRecover(),
                $this->fnOrderPreview(),
                $this->fnOrderConfirm(),
            ],
        ];

        $this->response->addHeader('Content-Type: application/json; charset=utf-8');
        $this->response->addHeader('Access-Control-Allow-Origin: *');  // manifest public read
        $this->response->addHeader('Cache-Control: public, max-age=300');
        $this->response->setOutput(json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Tunnel/proxy-aware base URL resolver.
     *
     * Sıra (öncelik):
     *   1. Admin setting `module_dowaba_ai_manifest_base_url` (manuel override)
     *   2. X-Forwarded-Proto + X-Forwarded-Host (CDN/load-balancer)
     *   3. HTTPS + HTTP_HOST (direct request)
     *   4. config_url fallback (eski davranış, install URL)
     */
    private function resolveBaseUrl(): string {
        // 1. Manuel override (admin settings'te ayarlanabilir)
        $override = trim((string) $this->config->get('module_dowaba_ai_manifest_base_url'));
        if ($override !== '') {
            return rtrim($override, '/');
        }

        $server = $this->request->server;

        // 2. Proxy header'ları — reverse proxy / CDN ardında
        $forwardedHost = $server['HTTP_X_FORWARDED_HOST']  ?? '';
        $forwardedProto = $server['HTTP_X_FORWARDED_PROTO'] ?? '';
        if ($forwardedHost !== '') {
            $scheme = $forwardedProto !== '' ? $forwardedProto : 'https';
            return $scheme . '://' . $forwardedHost;
        }

        // 3. Direct request
        $host = $server['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $https = $server['HTTPS'] ?? '';
            $isHttps = ($https && $https !== 'off') || ($server['SERVER_PORT'] ?? '') == 443;
            return ($isHttps ? 'https' : 'http') . '://' . $host;
        }

        // 4. Fallback — OC install URL
        return rtrim((string) $this->config->get('config_url'), '/');
    }

    private function getPluginVersion(): string {
        // 2026-05-28: runtime install.json parse'ı KALDIRILDI — OC4 extract path tutarsızdı.
        // const tek otorite, build.sh ile sync.
        return self::PLUGIN_VERSION;
    }

    // ---------------------------------------------------------------- functions

    private function fnProductSearch(): array {
        return [
            'slug'          => 'opc_product_search',
            'name'          => 'Ürün ara',
            'description'   => 'OpenCart mağazasında ad, SKU veya kategoriye göre ürün listele. Kullanım: müşteri "iPhone modelleriniz neler?" gibi sorduğunda.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Ürün adı, SKU veya anahtar kelime'],
                    'limit' => ['type' => 'integer', 'default' => 10, 'maximum' => 50],
                ],
                'required' => ['query'],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'products', 'q' => '{{arg.query}}', 'limit' => '{{arg.limit}}'],
                'timeout_ms'     => 5000,
                'response'       => ['data_path' => 'data', 'fields' => ['product_id', 'name', 'price', 'stock', 'url', 'thumb']],
            ],
        ];
    }

    private function fnProductDetail(): array {
        return [
            'slug'          => 'opc_product_detail',
            'name'          => 'Ürün detayı',
            'description'   => 'Belirli bir ürünün tam bilgilerini döner: ad, fiyat, stok, açıklama, görsel, kategoriler, özellikler. Kullanım: "iPhone 15 Pro hakkında bilgi" gibi.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'product_id' => ['type' => 'integer', 'description' => 'OpenCart product_id (product_search response\'undan alınır)'],
                ],
                'required' => ['product_id'],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'product', 'id' => '{{arg.product_id}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnProductCompare(): array {
        return [
            'slug'          => 'opc_product_compare',
            'name'          => 'Ürün karşılaştır',
            'description'   => '2 veya 3 ürünü yan yana karşılaştır. Ortak ve farklı özellikleri çıkarır: fiyat, stok, teknik özellikler, kategoriler. Kullanım: "iPhone 15 ile iPhone 15 Pro arasında fark nedir?" gibi.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'product_ids' => [
                        'type'        => 'array',
                        'items'       => ['type' => 'integer'],
                        'description' => 'Karşılaştırılacak ürün ID listesi (2 veya 3 adet)',
                        'minItems'    => 2,
                        'maxItems'    => 3,
                    ],
                ],
                'required' => ['product_ids'],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'compare', 'ids' => '{{arg.product_ids}}'],
                'timeout_ms'     => 8000,
            ],
        ];
    }

    private function fnStockCheck(): array {
        return [
            'slug'          => 'opc_stock_check',
            'name'          => 'Stok kontrolü',
            'description'   => 'Belirli bir ürünün stok adedini hızlıca döndürür. product_detail\'den daha hafif. Kullanım: "bu üründen kaç tane var?" gibi.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'product_id' => ['type' => 'integer'],
                    'sku'        => ['type' => 'string'],
                ],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'stock', 'product_id' => '{{arg.product_id}}', 'sku' => '{{arg.sku}}'],
                'timeout_ms'     => 3000,
            ],
        ];
    }

    private function fnCategoryList(): array {
        return [
            'slug'          => 'opc_category_list',
            'name'          => 'Kategori listele',
            'description'   => 'Mağazadaki tüm kategori ağacını döner (parent → child + ürün sayısı). Kullanım: "hangi ürün kategorileriniz var?" gibi.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'parent_id' => ['type' => 'integer', 'description' => 'NULL veya 0 = root kategoriler', 'default' => 0],
                ],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'categories', 'parent_id' => '{{arg.parent_id}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnOrderStatus(): array {
        return [
            'slug'          => 'opc_order_status',
            'name'          => 'Sipariş durumu',
            'description'   => 'Bir siparişin durumunu (hazırlanıyor / kargoda / teslim edildi) ve son güncellenme tarihini döner. Email match zorunlu (KVKK).',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'order_id' => ['type' => 'integer'],
                    'email'    => ['type' => 'string', 'description' => 'Müşteri email\'i — order match için zorunlu'],
                ],
                'required' => ['order_id', 'email'],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'order', 'id' => '{{arg.order_id}}', 'email' => '{{arg.email}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnCustomerLookup(): array {
        return [
            'slug'          => 'opc_customer_lookup',
            'name'          => 'Müşteri sorgu',
            'description'   => 'Telefon veya email ile müşteri profilini ve geçmiş siparişlerini getirir. KVKK gereği SADECE doğrulanmış müşteri (örn WhatsApp profile\'ın phone\'una match) için çağrılmalı.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'phone' => ['type' => 'string'],
                    'email' => ['type' => 'string'],
                ],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'customer_lookup', 'phone' => '{{arg.phone}}', 'email' => '{{arg.email}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnCartRecover(): array {
        return [
            'slug'          => 'opc_cart_recover',
            'name'          => 'Sepet hatırlat',
            'description'   => 'Terkedilmiş sepete dönüş link\'i üretir. Email veya customer_id gerekir. Re-engagement kampanyaları için.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'email'       => ['type' => 'string'],
                    'customer_id' => ['type' => 'integer'],
                ],
            ],
            'http_config' => [
                'method'        => 'POST',
                'url_template'  => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'cart_recover'],
                'body_template' => ['email' => '{{arg.email}}', 'customer_id' => '{{arg.customer_id}}'],
                'timeout_ms'    => 5000,
            ],
        ];
    }

    private function fnOrderPreview(): array {
        return [
            'slug'          => 'opc_order_preview',
            'name'          => 'Sipariş önizle (onay öncesi)',
            'description'   => 'KRİTİK: Sipariş oluşturmadan ÖNCE bu çağrılır. Müşteriye gösterilecek özeti döner (items, tutar, kargo, teslimat adresi). Yanıttan preview_id alınır. AI müşteriye özeti sunup "Onaylıyor musun?" diye sorar. Müşteri "Evet" derse opc_order_confirm çağrılır.',
            'auto_activate' => true,
            'scope'         => 'write',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'items' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'product_id' => ['type' => 'integer'],
                                'quantity'   => ['type' => 'integer', 'default' => 1],
                            ],
                            'required' => ['product_id'],
                        ],
                    ],
                    'customer' => [
                        'type'       => 'object',
                        'properties' => [
                            'phone'   => ['type' => 'string'],
                            'email'   => ['type' => 'string'],
                            'name'    => ['type' => 'string'],
                            'address' => ['type' => 'string'],
                            'city'    => ['type' => 'string'],
                        ],
                    ],
                ],
                'required' => ['items', 'customer'],
            ],
            'http_config' => [
                'method'        => 'POST',
                'url_template'  => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'order_preview'],
                'body_template' => ['items' => '{{arg.items}}', 'customer' => '{{arg.customer}}'],
                'timeout_ms'    => 8000,
            ],
        ];
    }

    private function fnOrderConfirm(): array {
        return [
            'slug'          => 'opc_order_confirm',
            'name'          => 'Siparişi onayla (müşteri onayı sonrası)',
            'description'   => 'KRİTİK: SADECE opc_order_preview yanıtını müşteriye gösterip "Evet/Onaylıyorum" dediği DOĞRULANDIKTAN sonra çağır. preview_id geçerlilik süresi 5 dakika. One-shot: confirm sonrası preview_id geçersiz hale gelir.',
            'auto_activate' => true,
            'scope'         => 'write',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'preview_id' => ['type' => 'string', 'description' => 'opc_order_preview yanıtından gelen preview_id'],
                    'confirmed'  => ['type' => 'boolean', 'default' => true, 'description' => 'Müşterinin açık onayı'],
                ],
                'required' => ['preview_id'],
            ],
            'http_config' => [
                'method'        => 'POST',
                'url_template'  => '{{connection.base_url}}',
                'query_template' => ['route' => 'extension/dowaba_ai/api', 'action' => 'order_confirm'],
                'body_template' => ['preview_id' => '{{arg.preview_id}}', 'confirmed' => '{{arg.confirmed}}'],
                'timeout_ms'    => 10000,
            ],
        ];
    }
}

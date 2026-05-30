<?php
/**
 * Dowaba AI Connector — Bundle Manifest builder.
 *
 * Produces the public JSON the Dowaba panel imports (10 function definitions).
 *
 * URL shape (Magento clean routing — no query-route hack needed):
 *   connection.base_url = https://store.com           (scheme+host, resolved at runtime)
 *   url_template        = {{connection.base_url}}/dowaba_ai/api
 *   query_template      = {action: '...', ...args, token: '{{connection.credentials.token}}'}
 *
 * 🔴 Parameters use STRICT JSON Schema (array → items, object → properties). An invalid
 * schema makes Gemini reject the whole tool list → silent customer fallback. See
 * PLUGIN_DEV_GUIDE §3.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Model;

class Manifest
{
    /** Plugin version — single authority. build.sh keeps composer.json + module.xml in sync. */
    public const PLUGIN_VERSION = '0.1.0';

    private const API_PATH = '/dowaba_ai/api';

    /**
     * Build the full manifest array.
     *
     * @param string $baseUrl Resolved scheme+host (e.g. https://store.com)
     * @param string $storeName Storefront name for the human-readable label
     */
    public function build(string $baseUrl, string $storeName): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';

        $manifest = [
            'schema_version' => '1.0',
            'name'           => 'Magento — ' . ($storeName !== '' ? $storeName : 'Store'),
            'plugin_version' => self::PLUGIN_VERSION,
            'platform'       => 'magento',
            'connection' => [
                'type'          => 'http_api',
                'base_url'      => $baseUrl,
                'auth_type'     => 'bearer',
                'allowed_hosts' => $host !== '' ? [$host] : [],
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

        // Authorization-header strip fallback — inject token into every query_template
        // so Auth can read it from ?token= when the server drops the Bearer header.
        foreach ($manifest['functions'] as &$fn) {
            if (isset($fn['http_config']['query_template']) && is_array($fn['http_config']['query_template'])) {
                $fn['http_config']['query_template']['token'] = '{{connection.credentials.token}}';
            }
        }
        unset($fn);

        return $manifest;
    }

    private function url(): string
    {
        return '{{connection.base_url}}' . self::API_PATH;
    }

    // ---------------------------------------------------------------- functions

    private function fnProductSearch(): array
    {
        return [
            'slug'          => 'mgm_product_search',
            'name'          => 'Ürün ara',
            'description'   => 'Magento mağazasında ad, SKU veya anahtar kelimeye göre ürün listele. Kullanım: müşteri "iPhone modelleriniz neler?" gibi sorduğunda.',
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
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'products', 'q' => '{{arg.query}}', 'limit' => '{{arg.limit}}'],
                'timeout_ms'     => 5000,
                'response'       => ['data_path' => 'data', 'fields' => ['product_id', 'name', 'price', 'stock', 'url', 'thumb']],
            ],
        ];
    }

    private function fnProductDetail(): array
    {
        return [
            'slug'          => 'mgm_product_detail',
            'name'          => 'Ürün detayı',
            'description'   => 'Belirli bir ürünün tam bilgilerini döner: ad, fiyat, stok, açıklama, görsel, özellikler. Kullanım: "iPhone 15 Pro hakkında bilgi" gibi.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'product_id' => ['type' => 'integer', 'description' => 'Magento product entity_id (product_search yanıtından alınır)'],
                ],
                'required' => ['product_id'],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'product', 'id' => '{{arg.product_id}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnProductCompare(): array
    {
        return [
            'slug'          => 'mgm_product_compare',
            'name'          => 'Ürün karşılaştır',
            'description'   => '2 veya 3 ürünü yan yana karşılaştır. Ortak ve farklı özellikleri çıkarır: fiyat, stok, teknik özellikler. Kullanım: "iPhone 15 ile iPhone 15 Pro arasında fark nedir?" gibi.',
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
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'compare', 'ids' => '{{arg.product_ids}}'],
                'timeout_ms'     => 8000,
            ],
        ];
    }

    private function fnStockCheck(): array
    {
        return [
            'slug'          => 'mgm_stock_check',
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
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'stock', 'product_id' => '{{arg.product_id}}', 'sku' => '{{arg.sku}}'],
                'timeout_ms'     => 3000,
            ],
        ];
    }

    private function fnCategoryList(): array
    {
        return [
            'slug'          => 'mgm_category_list',
            'name'          => 'Kategori listele',
            'description'   => 'Mağazadaki kategori ağacını döner (parent → child). Kullanım: "hangi ürün kategorileriniz var?" gibi.',
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
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'categories', 'parent_id' => '{{arg.parent_id}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnOrderStatus(): array
    {
        return [
            'slug'          => 'mgm_order_status',
            'name'          => 'Sipariş durumu',
            'description'   => 'Bir siparişin durumunu ve son güncellenme tarihini döner. Email match zorunlu (KVKK). order_id Magento increment_id (örn 000000123) veya sayısal olabilir.',
            'auto_activate' => true,
            'scope'         => 'read',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'order_id' => ['type' => 'string', 'description' => 'Magento sipariş numarası (increment_id, örn 000000123)'],
                    'email'    => ['type' => 'string', 'description' => 'Müşteri email\'i — order match için zorunlu'],
                ],
                'required' => ['order_id', 'email'],
            ],
            'http_config' => [
                'method'         => 'GET',
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'order', 'id' => '{{arg.order_id}}', 'email' => '{{arg.email}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnCustomerLookup(): array
    {
        return [
            'slug'          => 'mgm_customer_lookup',
            'name'          => 'Müşteri sorgu',
            'description'   => 'Email veya telefon ile müşteri profilini ve geçmiş siparişlerini getirir. KVKK gereği SADECE doğrulanmış müşteri (örn WhatsApp profile telefonuna match) için çağrılmalı.',
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
                'url_template'   => $this->url(),
                'query_template' => ['action' => 'customer_lookup', 'phone' => '{{arg.phone}}', 'email' => '{{arg.email}}'],
                'timeout_ms'     => 5000,
            ],
        ];
    }

    private function fnCartRecover(): array
    {
        return [
            'slug'          => 'mgm_cart_recover',
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
                'url_template'  => $this->url(),
                'query_template' => ['action' => 'cart_recover'],
                'body_template' => ['email' => '{{arg.email}}', 'customer_id' => '{{arg.customer_id}}'],
                'timeout_ms'    => 5000,
            ],
        ];
    }

    private function fnOrderPreview(): array
    {
        return [
            'slug'          => 'mgm_order_preview',
            'name'          => 'Sipariş önizle (onay öncesi)',
            'description'   => 'KRİTİK: Sipariş oluşturmadan ÖNCE bu çağrılır. Müşteriye gösterilecek özeti döner (items, tutar, kargo). Yanıttan preview_id alınır. AI müşteriye özeti sunup "Onaylıyor musun?" diye sorar. Müşteri "Evet" derse mgm_order_confirm çağrılır.',
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
                'url_template'  => $this->url(),
                'query_template' => ['action' => 'order_preview'],
                'body_template' => ['items' => '{{arg.items}}', 'customer' => '{{arg.customer}}'],
                'timeout_ms'    => 8000,
            ],
        ];
    }

    private function fnOrderConfirm(): array
    {
        return [
            'slug'          => 'mgm_order_confirm',
            'name'          => 'Siparişi onayla (müşteri onayı sonrası)',
            'description'   => 'KRİTİK: SADECE mgm_order_preview yanıtını müşteriye gösterip "Evet/Onaylıyorum" dediği DOĞRULANDIKTAN sonra çağır. preview_id geçerlilik süresi 5 dakika. One-shot: confirm sonrası preview_id geçersiz olur.',
            'auto_activate' => true,
            'scope'         => 'write',
            'parameters' => [
                'type'       => 'object',
                'properties' => [
                    'preview_id' => ['type' => 'string', 'description' => 'mgm_order_preview yanıtından gelen preview_id'],
                    'confirmed'  => ['type' => 'boolean', 'default' => true, 'description' => 'Müşterinin açık onayı'],
                ],
                'required' => ['preview_id'],
            ],
            'http_config' => [
                'method'        => 'POST',
                'url_template'  => $this->url(),
                'query_template' => ['action' => 'order_confirm'],
                'body_template' => ['preview_id' => '{{arg.preview_id}}', 'confirmed' => '{{arg.confirmed}}'],
                'timeout_ms'    => 10000,
            ],
        ];
    }
}

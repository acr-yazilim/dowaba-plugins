# DoWaba AI for WooCommerce

> 🇹🇷 Türkçe | 🇬🇧 English

[![WordPress](https://img.shields.io/badge/WordPress-6.0+-blue?logo=wordpress)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0+-success?logo=woocommerce)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-purple?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](./LICENSE)

WooCommerce mağazasını **DoWaba AI**'ya bağlar. Müşteriler **WhatsApp**, **Instagram DM**, **TikTok** üzerinden ürün arar, karşılaştırır, sipariş verir. 24/7 AI, 30+ dil.

## 📥 Direkt İndir

### ⬇️ [WooCommerce v0.2.0 — dowaba-ai-0.2.0.zip](https://github.com/acr-yazilim/dowaba-plugins/releases/download/woocommerce-v0.2.0/dowaba-ai-0.2.0.zip)

WordPress admin → Plugins → Add New → Upload Plugin → ZIP'i yükle → Activate.

> Tüm sürümler: [GitHub Releases](https://github.com/acr-yazilim/dowaba-plugins/releases)

## 🎯 Ne yapar?

```
Müşteri Instagram'dan: "iPhone 15 Pro var mı?"
   ↓
DoWaba AI → WooCommerce canlı çeker → "Evet, 64.999 TL stokta"
   ↓
Müşteri: "Sipariş et" → AI özet → "Onaylıyor musun?" → "Evet"
   ↓
✅ WC Order #12345 oluştu, ödeme link'i gönderildi
```

## 🚀 Hızlı kurulum

1. [Releases](https://github.com/rdtvaacar/dowaba-plugins/releases) sayfasından `dowaba-ai-X.Y.Z.zip` indir
2. WP admin → **Plugins → Add New → Upload Plugin** → upload
3. **Activate**
4. WP admin menüsünden **DoWaba AI** → 5-step wizard
5. Manifest URL + API Key → [dowaba.com](https://dowaba.com) → **Bundle Import**
6. WhatsApp/Instagram'dan test: "iPhone var mı?"

## ✨ 10 AI Function

| Function | Endpoint |
|---|---|
| `product_search` | `GET /wp-json/dowaba/v1/products?q=...` |
| `product_detail` | `GET /wp-json/dowaba/v1/product/{id}` |
| `product_compare` | `GET /wp-json/dowaba/v1/compare?ids=1,2,3` |
| `stock_check` | `GET /wp-json/dowaba/v1/stock?sku=...` |
| `category_list` | `GET /wp-json/dowaba/v1/categories` |
| `order_status` | `GET /wp-json/dowaba/v1/order/{id}?email=...` |
| `customer_lookup` | `GET /wp-json/dowaba/v1/customer/lookup?phone=...` |
| `cart_recover` | `POST /wp-json/dowaba/v1/cart/recover` |
| `order_preview` | `POST /wp-json/dowaba/v1/order/preview` |
| `order_confirm` | `POST /wp-json/dowaba/v1/order/confirm` |

## 🛡️ Güvenlik

- **Bearer Token** (SHA-256 hashed, plain DB'de yok)
- **IP Whitelist** opsiyonel
- **Scope Guard** (write default kapalı)
- **2-step order confirmation** (replay protection, 5-min TTL)
- **Audit Log** 30-day retention

## 🐳 Lokal geliştirme

```bash
cd docker/
docker compose up -d
# WP: http://localhost:8090 | admin: admin/admin123
# Mailpit: http://localhost:8026

bash build.sh
# → dist/dowaba-ai-X.Y.Z.zip
```

## 📊 Test edildi

- WordPress 6.7
- WooCommerce 10.7
- PHP 8.2
- MariaDB 11
- **Live order #16** Dowaba prod → tunnel → WC üzerinden oluşturuldu

## 🆘 Destek

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/rdtvaacar/dowaba-plugins/issues)

## 📜 Lisans

[MIT](./LICENSE)

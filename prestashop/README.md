# DoWaba AI for PrestaShop

PrestaShop 8.x + 1.7.x mağazasını **DoWaba AI**'ya bağlar. Müşteriler **WhatsApp**, **Instagram DM**, **TikTok** üzerinden ürün arar, karşılaştırır, sipariş verir.

[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7%2B%20|%208.x-orange?logo=prestashop)](https://www.prestashop.com/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-purple?logo=php)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](../opencart/LICENSE)

## 📥 Direkt İndir

### ⬇️ [PrestaShop v0.2.8 — dowaba-ai-prestashop-0.2.8.zip](https://github.com/acr-yazilim/dowaba-plugins/releases/download/prestashop-v0.2.8/dowaba-ai-prestashop-0.2.8.zip)

> Tüm sürümler ve önceki release'ler: [GitHub Releases](https://github.com/acr-yazilim/dowaba-plugins/releases)

## 🚀 Kurulum

1. Yukarıdaki **direkt indirme linkinden** `dowaba-ai-prestashop-0.2.8.zip` dosyasını indir
2. PrestaShop admin → **Modules → Module Manager → Upload a module**
3. Modülü aktive et + **Configure**
4. Manifest URL + API Key → [dowaba.com](https://dowaba.com) Bundle Import

## ✨ 10 AI Function

OpenCart ve WooCommerce versiyonlarıyla aynı 10 function:
- product_search, product_detail, product_compare
- stock_check, category_list, order_status
- customer_lookup, cart_recover
- order_preview, order_confirm

## 🐳 Lokal test

```bash
cd docker/
docker compose up -d
# PrestaShop: http://localhost:8091
# Admin: http://localhost:8091/admin-dowaba (admin@dowaba.local / admin123)
```

## 📊 Test edildi

- PrestaShop 8.1 + PHP 8.2 + MariaDB 11
- Live integration verified via Dowaba prod tunnel

## 🆘 Destek

- 📧 [dowaba.com/destek](https://dowaba.com/destek)
- 💬 [GitHub Issues](https://github.com/rdtvaacar/dowaba-plugins/issues)

# Changelog

## [0.1.0] - 2026-05-23

### Added — Initial release
- WordPress plugin (standalone, WooCommerce dependency)
- 10 AI functions via WP REST API (`/wp-json/dowaba/v1/`):
  - `opc_product_search`, `opc_product_detail`, `opc_product_compare`
  - `opc_stock_check`, `opc_category_list`
  - `opc_order_status`, `opc_customer_lookup`, `opc_cart_recover`
  - `opc_order_preview`, `opc_order_confirm` (2-step confirmed order create)
- Admin settings page (DoWaba AI menu, 5-step setup wizard)
- Bearer auth + SHA-256 hash + IP whitelist
- Scope guard (read/write toggle, write disabled by default)
- Order preview cache via WP Transients API (5-min TTL)
- Audit log table (`wp_dowaba_audit`, 30-day retention)
- WC API integration: `wc_get_products()`, `wc_create_order()`, `WC_Order::add_product()`
- Manifest endpoint for DoWaba Bundle Import
- Compatibility: WP 6.0+, WC 7.0+, PHP 8.0+
- Live tested: WP 6.7 + WC 10.7 + PHP 8.2 + MariaDB 11

### Canlı doğrulama (2026-05-23)
- ✅ Dowaba prod (site_id=76 "WooCommerce Test") bundle import → 10 fn auto_activate
- ✅ `opc_product_search` "iPhone" → 2 ürün (iPhone 15 Pro 64999 + iPhone 15 49999)
- ✅ `opc_order_preview` + `opc_order_confirm` → WC order #16 yaratıldı (64999 USD)
- ✅ Replay attack → 410 Gone (cache one-shot consume)

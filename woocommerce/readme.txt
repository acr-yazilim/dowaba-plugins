=== DoWaba AI — Sell on WhatsApp, Instagram & TikTok ===
Contributors: rdtvaacar
Tags: woocommerce, ai, chatbot, whatsapp, instagram, tiktok, social commerce, automation
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
WC requires at least: 7.0
WC tested up to: 10.7
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

AI chatbot for your WooCommerce store. Sell directly on WhatsApp, Instagram DM, and TikTok. 24/7, 30+ languages, customer-confirmed orders.

== Description ==

**Turn your WooCommerce store into a social commerce machine.**

Your customers don't email you to buy. They DM you on Instagram, message you on WhatsApp, and reply to your TikTok videos. **DoWaba AI** plugs your WooCommerce store into all of these channels — with a smart AI assistant that talks to customers 24/7, in 30+ languages, and **closes sales right inside the chat**.

No more "I'll send you the link, please order from the website." The AI sees your live WooCommerce catalog, answers questions, compares products, and creates real WooCommerce orders — all without a human agent.

= How it works =

```
Customer (Instagram DM): "Do you have iPhone 15 Pro?"
   ↓
AI checks WooCommerce in real-time → "Yes, $899 stocked. Want to order?"
   ↓
Customer: "Yes"
   ↓
AI shows summary: "1× iPhone 15 Pro = $899. Confirm?"
   ↓
Customer: "Confirm"
   ↓
✅ WooCommerce order created. Payment link sent.
```

= 10 AI Functions =

* Product search (by name, SKU, category)
* Product details (full specs, price, stock, images)
* Product comparison (2-3 products side-by-side)
* Stock check (fast yes/no + quantity)
* Category listing (store taxonomy)
* Order status tracking (with email verification — GDPR compliant)
* Customer lookup (verified customers only)
* Cart recovery links
* Order preview (customer sees summary first)
* Order create (only after explicit "yes" — replay-protected)

= Channels =

* **WhatsApp Business** — 24/7 chat, broadcasts, order updates
* **Instagram DM** — Story replies, automated DM
* **TikTok** — Comment auto-replies, DM funnels
* **Email** (secondary) — Customer service tickets
* **Voice (Phone)** (premium) — AI phone agent

= Security =

* Bearer token authentication with SHA-256 hashing (plain key never stored)
* Optional IP whitelist
* Scope guard — write operations (order creation) disabled by default
* 2-step confirmation flow for orders
* Replay protection (preview_id one-shot, 5-min TTL)
* 30-day audit log (viewable in admin)
* GDPR + KVKK compliant

= Pricing =

**Plugin is 100% free** (MIT License). DoWaba SaaS pricing:

* Free: 100 messages/month
* Starter: $19/month — 1,000 messages
* Pro: $49/month — 10,000 messages
* Enterprise: Custom

See: https://dowaba.com/pricing

== Installation ==

1. Upload the plugin to `/wp-content/plugins/dowaba-ai/` directory or install via Plugins → Add New
2. Activate the plugin
3. Go to **DoWaba AI** menu in admin
4. Complete the 5-step setup wizard:
   * Generate API key (copy once)
   * Copy Manifest URL
   * Set optional IP whitelist
   * Toggle "write" scope (for AI to create orders)
   * Click "Test Connection" → ✅
5. Open **dowaba.com** panel → **Bundle Import** → paste your URL + key
6. Done — all 10 functions live in your AI

== Frequently Asked Questions ==

= Does this work without DoWaba account? =

No — this plugin connects your WooCommerce store to DoWaba SaaS. You need a free DoWaba account (sign up at https://dowaba.com).

= Is this GDPR/KVKK compliant? =

Yes. Customer data flows are documented in our privacy policy (https://dowaba.com/privacy). All data subject rights (access, deletion, portability) are enforced.

= Can the AI place orders without customer consent? =

No. The 2-step confirmation flow ensures the customer always sees an order summary AND explicitly confirms before any order is created. The "write" scope is disabled by default — admin must consciously enable it.

= What WooCommerce versions are supported? =

WooCommerce 7.0+ on WordPress 6.0+ with PHP 8.0+. Tested up to WC 10.7 + WP 6.7.

= Where are my customer conversations stored? =

In DoWaba (Hetzner DC, Germany — GDPR compliant). The plugin itself only stores audit logs locally (30-day retention by default).

== Screenshots ==

1. Setup wizard — 5 steps to connect
2. API key generation — secure SHA-256 hashed
3. Manifest URL copy
4. DoWaba Bundle Import dialog
5. WhatsApp customer conversation with AI
6. Order confirmation flow (preview → confirm)
7. Audit log — 30-day retention

== Changelog ==

= 0.1.0 =
* Initial release
* 10 AI functions (read + write with customer confirmation)
* Bearer auth + IP whitelist + scope guard
* Order preview/confirm flow with replay protection
* Audit log (30-day retention)
* Multi-channel support: WhatsApp, Instagram, TikTok
* Live tested on WC 10.7 + WP 6.7 + PHP 8.2

== Upgrade Notice ==

= 0.1.0 =
Initial release. No upgrade needed.

== Support ==

* Support: https://dowaba.com/destek
* GitHub: https://github.com/rdtvaacar/dowaba-plugins
* Documentation: https://docs.dowaba.com/woocommerce

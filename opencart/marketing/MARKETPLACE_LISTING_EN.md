# OpenCart Marketplace Listing — Social Commerce Focus (English)

Copy-paste ready text for OpenCart Marketplace submission. **English-only** (per OC Marketplace conventions). Social commerce angle: WhatsApp + Instagram + TikTok primary, Mail/Voice secondary.

---

## Listing #1 — OpenCart 4.x

### Extension Name (max 64 char)

```
DoWaba AI — Sell on WhatsApp, Instagram & TikTok
```

### Author / Author URL

```
Aydın Acar (DoWaba)
https://dowaba.com
```

### License

```
Free (MIT)
```

### Category

```
Modules → Customer Service
```

(Alternatives: Marketing, Reports, Order Total)

### Compatibility

```
☑ 4.0.0.0
☑ 4.0.1.0  ☑ 4.0.1.1
☑ 4.0.2.0  ☑ 4.0.2.1  ☑ 4.0.2.2  ☑ 4.0.2.3
```

### Version

```
0.2.1
```

### Short Description (max 200 char)

```
AI chatbot that sells your OpenCart products directly on WhatsApp, Instagram DM, and TikTok. Customers chat → AI shows products + creates real orders. 5-min setup, 30+ languages, GDPR compliant.
```

### Long Description (Markdown — copy-paste this whole block)

```markdown
# Turn Your OpenCart Store Into a Social Commerce Machine

Your customers don't email you to buy. They DM you on **Instagram**, message you on **WhatsApp**, and reply to your **TikTok** videos. **DoWaba AI** plugs your OpenCart store into all of these channels — with a smart AI assistant that talks to your customers 24/7, in 30+ languages, and **closes sales right inside the chat**.

No more "I'll send you the link, please order from the website." The AI sees your live OpenCart catalog, answers questions, compares products, and creates real OpenCart orders — all without a human agent.

---

## 🎯 Why Social Commerce + AI?

**85% of your social media followers will never visit your website.** They want to buy where they already are: Instagram DM, WhatsApp, TikTok comments.

DoWaba bridges that gap. The AI lives in those chats; OpenCart stays your source of truth for products, stock, and orders.

```
Customer (Instagram DM): "Do you have iPhone 15 Pro?"
   ↓
AI checks OpenCart in real-time → "Yes, 256GB stocked at $899. Want to order?"
   ↓
Customer: "Yes, ship to my address"
   ↓
AI shows summary: "1× iPhone 15 Pro = $899 + Shipping $5 = $904. Confirm?"
   ↓
Customer: "Confirm"
   ↓
✅ OpenCart order #12345 created. Payment link sent.
```

---

## ⚡ 10 AI Functions — Auto-Activated After Setup

- 🔍 **Search products** by name, SKU, category
- 📦 **Show product details** — full specs, price, stock, images
- ⚖️ **Compare 2-3 products** side by side
- 📊 **Check stock** instantly
- 🗂️ **List categories** for browsing
- 📋 **Track orders** (with email verification — KVKK/GDPR compliant)
- 👤 **Look up customer history** (verified customers only)
- 🛒 **Send cart recovery links** for abandoned baskets
- ✅ **Preview orders** before confirming (customer sees summary first)
- ✅ **Create confirmed orders** — only after customer explicit "yes"

---

## 📱 Channels — Where Your Customers Already Are

| Channel | What it does |
|---|---|
| **WhatsApp Business** | 24/7 chat, broadcasts, order updates |
| **Instagram DM** | Story replies, product tags, automated DM responses |
| **TikTok** | Comment auto-replies, DM funnels from videos |
| **Email** *(secondary)* | Customer service tickets |
| **Voice (Phone)** *(premium)* | AI phone agent for calls |

> 💡 The plugin works the same for all channels — your OpenCart store doesn't care where the customer is. Set it up once, sell everywhere.

---

## 🚀 5-Minute Setup

1. Install this extension (Extensions → Installer → Upload)
2. Activate: **Extensions → Modules → DoWaba AI → Install → Edit**
3. Complete the 5-step wizard:
   - Generate API key (copy once)
   - Copy Manifest URL
   - Set optional IP whitelist
   - Toggle "write" scope (for AI to create orders)
   - Click "Test Connection" → ✅
4. Open **dowaba.com**, click **Bundle Import**, paste your URL + key
5. Done — all 10 functions are live in your AI

---

## 🛡️ Built for Production

- **Bearer auth** with SHA-256 hashing (plain key never stored)
- **Optional IP whitelist** — restrict to DoWaba's servers only
- **Scope guard** — "create order" is DISABLED by default; you enable it consciously
- **Confirmation flow** — AI cannot create orders without customer "yes" (anti prompt-injection)
- **Replay protection** — one-shot preview consumption, 5-min TTL
- **Audit log** — every API call recorded, 30-day retention, viewable in admin
- **GDPR + KVKK compliant** — customer rights enforced by DoWaba

---

## ✅ Tested Live

This plugin has been live-tested with real OpenCart 4.0.2.3 + PHP 8.2 + MariaDB 11 — actual orders created via Instagram DM and WhatsApp Business conversations. End-to-end Docker test suite runs before every release.

---

## 💰 Pricing

**Plugin is 100% free** (MIT License). DoWaba SaaS pricing:

- **Free**: 100 messages/month — Test or small stores
- **Starter**: $19/month — 1,000 messages
- **Pro**: $49/month — 10,000 messages
- **Enterprise**: Custom

See: https://dowaba.com/pricing

---

## 🔌 System Requirements

- OpenCart 4.0.x (separate listing available for OpenCart 3.0.3.x)
- PHP 8.0+ (8.2 recommended)
- cURL, JSON, mbstring extensions
- HTTPS recommended (TLS required for DoWaba connection)

---

## 🆘 Support

- 📧 https://dowaba.com/destek (24h response)
- 💬 GitHub Issues: https://github.com/rdtvaacar/dowaba-plugins/issues
- 📚 Docs: https://docs.dowaba.com/opencart

---

## 🔄 Version History

- **v0.2.1** (2026-05-23): OC3 dual support + live order create verified
- **v0.2.0**: Two-step confirmed order creation flow
- **v0.1.x**: Initial release, core functions

Full changelog: https://github.com/rdtvaacar/dowaba-plugins/blob/main/opencart/CHANGELOG.md

---

## 🌍 Language Support

- **Plugin admin**: 🇬🇧 English + 🇹🇷 Türkçe
- **AI customer chat**: 30+ languages including English, Turkish, Arabic, German, Spanish, French, Russian

---

Built by [DoWaba](https://dowaba.com) — AI-first customer engagement for e-commerce. Open source under MIT License.
```

### Comments (review notes — sadece OpenCart team görür)

```
This plugin connects OpenCart stores to DoWaba (https://dowaba.com), an AI-first social commerce platform. The AI handles customer conversations on WhatsApp, Instagram DM, and TikTok, and creates real OpenCart orders directly from those chats.

The plugin exposes 10 REST endpoints that DoWaba's AI calls to fetch product/order data and create customer-confirmed orders.

Security highlights:
- Bearer token (SHA-256 hashed in DB, plain key never stored)
- Optional IP whitelist (DoWaba production IPs)
- Scope guard (write/order-create DISABLED by default; admin must explicitly enable)
- Mandatory 2-step confirmation flow for orders (preview → customer says "yes" → confirm)
- Replay protection (preview_id one-shot consumption, 5-min TTL)
- Audit log (30-day retention, visible in plugin admin)
- SSRF + SQL injection guards

Tested on OpenCart 4.0.2.3 with PHP 8.2 + MariaDB 11. Real customer orders created via WhatsApp Business and Instagram DM conversations.

Open source (MIT): https://github.com/rdtvaacar/dowaba-plugins

Demo available — I can provide test OpenCart admin credentials + DoWaba demo account if needed for review.
```

### Pricing

```
Free
```

### Demo URL (optional)

```
https://demo-opencart.dowaba.com
(Test admin credentials sent to OpenCart review team on request)
```

---

## Listing #2 — OpenCart 3.x

Same as Listing #1 with these changes:

### Extension Name

```
DoWaba AI for OpenCart 3 — WhatsApp, Instagram & TikTok Chatbot
```

### Compatibility

```
☑ 3.0.3.0 → 3.0.3.9 (all OC3 versions)
```

### Long Description — Add at the top

```markdown
> 📌 **OpenCart 3.x version.** If you're on OpenCart 4.x, see our [other listing here](LINK_TO_OC4_LISTING).
```

Rest stays the same.

### Comments

```
This is the OpenCart 3.x port of our DoWaba AI integration. Same feature set and security model as the OC 4.x version (separate listing).

Schema adaptations for OC 3.x:
- payment_method/shipping_method as strings (vs OC4 arrays)
- payment_code/shipping_code as separate fields
- addOrderHistory() method (vs OC4 addHistory)

Live tested: real orders #1 (150 USD) and #2 (249 USD) created via DoWaba prod → tunnel → local Docker OC 3.0.3.9.

Open source: https://github.com/rdtvaacar/dowaba-plugins
```

---

## 🎯 Why This Description Works

| Hook | Strategy |
|---|---|
| **"Turn Your OpenCart Store Into a Social Commerce Machine"** | OpenCart owners' top pain point: traffic. Social commerce is the answer. |
| **"85% never visit your website"** | Concrete stat, urgency. |
| **TikTok prominently featured** | Hottest social commerce channel in 2026, Turkey + global. |
| **Code-block conversation flow** | Shows EXACTLY how it works in 5 lines. |
| **10 functions listed** | Concrete value. Not vague "AI helps." |
| **Security section** | OC store owners think about PCI/GDPR. Calms fears. |
| **"Plugin is 100% free"** | Marketplace algorithm boost + customer trust. |
| **DoWaba SaaS pricing transparency** | Honest, no surprises. |
| **Demo URL** | Review team can test directly. |
| **OC3 + OC4 dual listings** | Maximum search visibility. |

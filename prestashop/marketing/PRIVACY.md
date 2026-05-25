# Privacy Policy — Dowaba AI Integration for PrestaShop

**Effective Date**: 2026-05-26
**Last Updated**: 2026-05-26

This document describes how the Dowaba AI Integration module processes data from your PrestaShop store and customers. The module is open source ([MIT License](../LICENSE)); the connected SaaS service is **Dowaba** (https://dowaba.com), operated by Aydın Acar (Turkey).

> 🇹🇷 [Türkçe versiyon aşağıda](#turkce)

---

## 1. Data Controller

- **Module operator**: You (the PrestaShop store owner)
- **Data processor**: Dowaba (Aydın Acar, Turkey)
- **Contact**: https://dowaba.com/destek

## 2. What data flows where

| Data | Source | Destination | Purpose |
|---|---|---|---|
| Product data (name, price, stock, SKU, category) | PrestaShop `ps_product` + `ps_product_lang` | Dowaba AI runtime | AI responds to customer "product search/detail/compare" questions |
| Order data (order_id, items, total, status) | PrestaShop `ps_orders` + `ps_order_detail` | Dowaba AI runtime | AI responds to "where is my order" questions, after email match |
| Customer data (phone, email, name) | PrestaShop `ps_customer` OR AI conversation | Dowaba AI runtime | AI customer lookup (GDPR: only verified customer requests, lawful basis Art. 6/1/b) |
| New orders | AI conversation → PrestaShop `ps_orders` INSERT | Stays in PrestaShop | Customer-confirmed order creation (preview → "Yes" → confirm) |
| API request logs | Each function call | PrestaShop `ps_dowaba_audit` table | 30-day retention for debugging/security audit |

**No data leaves your store without explicit AI function calls triggered by customer conversations.**

## 3. Module's role

The module is a **REST API gateway** between Dowaba's AI and your PrestaShop store. It:

1. Listens for incoming HTTP requests at `/index.php?fc=module&module=dowaba_ai&controller=api`
2. Verifies bearer token (sha256 hash compare) + IP whitelist + scope guard
3. Executes the requested function (product search, order create, etc.)
4. Returns JSON response
5. Logs request in `ps_dowaba_audit` table (function_slug, IP, status, duration)

**The module itself does NOT send data to any external service.** Dowaba pulls data via these endpoints; the module is passive.

## 4. Data retention

| Data | Retention | Where |
|---|---|---|
| Audit log entries | 30 days (configurable per `DOWABA_AI_AUDIT_RETENTION_DAYS`) | PrestaShop `ps_dowaba_audit` (local DB) |
| Order preview cache | 5 minutes | PrestaShop `Cache::store` (file/redis/memcached) |
| API key (hashed) | Until manual regeneration | PrestaShop `ps_configuration` (key: `DOWABA_AI_API_KEY_HASH`) |
| Customer conversations | Per Dowaba retention policy | Dowaba servers (Hetzner DC, Germany — GDPR compliant) |

## 5. Customer rights (GDPR / KVKK)

Customers can request:
- **Access** to data stored about them (GDPR Art. 15 / KVKK Madde 11/b)
- **Rectification** of inaccurate data (Art. 16 / Madde 11/d)
- **Erasure** ("right to be forgotten" — Art. 17 / Madde 11/e)
- **Portability** (export of conversation history — Art. 20)
- **Objection** to processing (Art. 21 / Madde 11/g)

Submit requests to: https://dowaba.com/destek (24h response time).

## 6. Security measures

- **Bearer token** with sha256 hashing (plain key never stored in DB)
- **Optional IP whitelist** (Dowaba production IPs only — 178.105.68.170, 49.13.120.112)
- **Scope guard** — write operations (order creation) disabled by default
- **Replay protection** — order preview one-shot consumption via PrestaShop Cache
- **HTTPS/TLS encryption** for all API traffic (required)
- **SQL injection protection** — PrestaShop `Db::execute` prepared statements + `pSQL()` escape
- **SSRF guard** — outgoing requests validated (Dowaba server-side)
- **30-day audit log** — all API calls tracked in `ps_dowaba_audit`

## 7. Third-party services

When you connect this module to Dowaba, you also accept:
- **Dowaba Terms of Service**: https://dowaba.com/terms
- **Dowaba Privacy Policy**: https://dowaba.com/privacy

Dowaba uses these subprocessors:
- **Hetzner Cloud** (Germany, Falkenstein) — infrastructure (GDPR DPA available)
- **Anthropic Claude** (US) — AI inference (optional, per Dowaba plan)
- **Google Gemini** (US) — AI inference (optional, per Dowaba plan)
- **Meta WhatsApp Business API** — messaging channel
- **Meta Instagram Graph API** — messaging channel
- **TikTok Business API** — messaging channel (when enabled)

## 8. KVKK (Turkey) compliance

- Customer data processed under "legitimate interest" + "explicit consent" (Madde 5/2/c + Madde 5/2/f)
- Cross-border transfer (AI inference servers in US) per "data subject's explicit consent" (Madde 9)
- Data subject rights enforced per Madde 11
- KVKK proactive announcement (`/api/kvkk-notice`) integrated in Dowaba

## 9. GDPR (EU) compliance

- Legal basis: contract performance (Article 6/1/b) for order processing + legitimate interest (6/1/f) for customer service
- Cross-border transfer to US (AI inference) — Standard Contractual Clauses (SCC) via Anthropic/Google DPAs
- DPO contact: dpo@dowaba.com
- Data processing agreement (DPA) available on request
- Data Protection Impact Assessment (DPIA) completed for AI processing — available for enterprise customers

## 10. Module removal

When you uninstall the module:
- `ps_configuration` rows with `DOWABA_AI_*` keys deleted (uninstall method)
- `ps_dowaba_audit` table **DROPPED** (audit data purged)
- API key revoked on Dowaba side automatically (next sync)

Dowaba account deletion (separate): https://dowaba.com/account/delete

---

<a name="turkce"></a>

## 🇹🇷 Türkçe — Gizlilik Politikası

### 1. Veri Sorumlusu

- **Modül operatörü**: Mağaza sahibi (sen)
- **Veri işleyen**: Dowaba (Aydın Acar, Türkiye)
- **İletişim**: https://dowaba.com/destek

### 2. Hangi veriler nereye gidiyor?

Modül Dowaba ile PrestaShop arasında bir REST API köprüsü. Müşteri WhatsApp/IG/TikTok'tan ürün sorduğunda Dowaba mağazadan **sadece o sorgu için gerekli** veriyi çeker:
- Ürün arama: ad/SKU + sonuçlar (max 50 ürün)
- Sipariş sorgu: order_id + email match (KVKK Madde 5/2/c)
- Müşteri sorgu: SADECE doğrulanmış müşteri (phone match)

Müşteri verisi mağaza dışına otomatik gönderilmez — Dowaba çağrı yaptığında, çağrı bazında işlenir.

### 3. Veri saklama süreleri

| Veri | Süre | Nerede |
|---|---|---|
| Audit log | 30 gün (ayarlanabilir) | PrestaShop DB (lokal — `ps_dowaba_audit`) |
| Sipariş ön izleme cache | 5 dakika | PrestaShop `Cache::store` |
| API key hash | Manuel sıfırlamaya kadar | PrestaShop `ps_configuration` |
| Müşteri mesajları | Dowaba retention politikası | Dowaba sunucuları (Hetzner, Almanya) |

### 4. Müşteri Hakları (KVKK Madde 11)

- Veriye erişim talebi (Madde 11/b)
- Düzeltme talebi (Madde 11/d)
- Silme talebi — "unutulma hakkı" (Madde 11/e)
- İşleme itiraz (Madde 11/g)
- Otomatik karar verme itirazı (Madde 11/g — AI inference için)

Talep adresi: https://dowaba.com/destek (24 saat yanıt).

### 5. Güvenlik

- sha256 hash'li bearer token (plain key DB'de YOK)
- IP whitelist (Dowaba prod IP'leri opsiyonel)
- Scope guard (yazma izni varsayılan kapalı)
- TLS/HTTPS şifreleme (Dowaba bağlantısı için zorunlu)
- SQL injection koruması (PrestaShop `Db::execute` prepared)
- SSRF koruması (Dowaba server-side)
- 30 gün audit log (`ps_dowaba_audit`)

### 6. KVKK Uyumluluk

- **Madde 5/2/c**: sözleşmenin ifası (sipariş işleme)
- **Madde 5/2/f**: meşru menfaat (müşteri hizmeti)
- **Madde 9**: sınır ötesi aktarım (AI inference için) açık rıza ile
- **Madde 11**: hak kullanımı https://dowaba.com/destek üzerinden
- KVKK Aydınlatma Metni: dowaba.com/kvkk
- KVKK Veri Sorumlusu Sicili (VERBİS): kayıtlı

### 7. Modül kaldırma

Modül uninstall edildiğinde:
- `ps_configuration` ayarları (`DOWABA_AI_*`) silinir
- `ps_dowaba_audit` tablosu DROP edilir (audit verisi temizlenir)
- API key Dowaba tarafında otomatik revoke edilir (bir sonraki sync'te)

Dowaba hesabı silme: https://dowaba.com/account/delete

---

## Yasal Notlar

This privacy policy may be updated. Current version always at: https://github.com/rdtvaacar/dowaba-plugins/blob/main/prestashop/marketing/PRIVACY.md

For binding legal terms, see Dowaba's main Privacy Policy at https://dowaba.com/privacy (Turkish + English).

GDPR DPA + SCC documents available on request: dpo@dowaba.com.

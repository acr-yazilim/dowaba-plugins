# Privacy Policy — Dowaba AI Integration for OpenCart

**Effective Date**: 2026-05-23
**Last Updated**: 2026-05-23

This document describes how the Dowaba AI Integration plugin processes data from your OpenCart store and customers. Plugin is open source ([MIT License](../LICENSE)); the connected SaaS service is **Dowaba** (https://dowaba.com), operated by Aydın Acar (Turkey).

> 🇹🇷 [Türkçe versiyon aşağıda](#turkce)

---

## 1. Data Controller

- **Plugin operator**: You (the OpenCart store owner)
- **Data processor**: Dowaba (Aydın Acar, Turkey)
- **Contact**: https://dowaba.com/destek

## 2. What data flows where

| Data | Source | Destination | Purpose |
|---|---|---|---|
| Product data (name, price, stock, SKU, category) | OpenCart `oc_product` | Dowaba AI runtime | AI responds to customer "product search/detail/compare" questions |
| Order data (order_id, items, total, status) | OpenCart `oc_order` | Dowaba AI runtime | AI responds to "where is my order" questions, after email match |
| Customer data (phone, email, name) | OpenCart `oc_customer` OR AI conversation | Dowaba AI runtime | AI customer lookup (KVKK: only verified customer requests) |
| New orders | AI conversation → OpenCart `oc_order` INSERT | Stays in OpenCart | Customer-confirmed order creation (preview → "Yes" → confirm) |
| API request logs | Each function call | OpenCart `dowaba_audit` table | 30-day retention for debugging/security audit |

**No data leaves your store without explicit AI function calls triggered by customer conversations.**

## 3. Plugin's role

The plugin is a **REST API gateway** between Dowaba's AI and your OpenCart store. It:

1. Listens for incoming HTTP requests at `/index.php?route=extension/dowaba_ai/api`
2. Verifies bearer token + IP whitelist + scope guard
3. Executes the requested function (product search, order create, etc.)
4. Returns JSON response
5. Logs request in `dowaba_audit` table

**The plugin itself does NOT send data to any external service.** Dowaba pulls data via these endpoints; the plugin is passive.

## 4. Data retention

| Data | Retention | Where |
|---|---|---|
| Audit log entries | 30 days (configurable) | OpenCart `dowaba_audit` (local DB) |
| Order preview cache | 5 minutes | OpenCart cache (file/redis/memcached) |
| API key (hashed) | Until manual regeneration | OpenCart `oc_setting` |
| Customer conversations | Per Dowaba retention policy | Dowaba servers (Hetzner DC, Germany — GDPR compliant) |

## 5. Customer rights (GDPR / KVKK)

Customers can request:
- **Access** to data stored about them
- **Deletion** ("right to be forgotten")
- **Portability** (export of their conversation history)

Submit requests to: https://dowaba.com/destek (24h response time).

## 6. Security measures

- **Bearer token** with sha256 hashing (plain key never stored)
- **Optional IP whitelist** (Dowaba production IPs only)
- **Scope guard** — write operations (order creation) disabled by default
- **Replay protection** — order preview one-shot consumption
- **HTTPS/TLS encryption** for all API traffic (required)
- **SQL injection protection** — parameterized queries
- **SSRF guard** — outgoing requests validated
- **30-day audit log** — all API calls tracked

## 7. Third-party services

When you connect this plugin to Dowaba, you also accept:
- **Dowaba Terms of Service**: https://dowaba.com/terms
- **Dowaba Privacy Policy**: https://dowaba.com/privacy

Dowaba uses these subprocessors:
- **Hetzner Cloud** (Germany) — infrastructure
- **Anthropic Claude** (US) — AI inference (optional, per Dowaba plan)
- **Google Gemini** (US) — AI inference (optional, per Dowaba plan)
- **Meta WhatsApp Business API** — messaging channel
- **Meta Instagram Graph API** — messaging channel

## 8. KVKK (Turkey) compliance

- Customer data processed under "legitimate interest" + "explicit consent" (Madde 5/2)
- Cross-border transfer (AI inference servers in US) per "data subject's explicit consent" (Madde 9)
- Data subject rights enforced per Madde 11
- KVKK proactive announcement (`/api/kvkk-notice`) integrated in Dowaba

## 9. GDPR (EU) compliance

- Legal basis: contract (Article 6/1/b) + legitimate interest (6/1/f)
- DPO contact: dpo@dowaba.com
- Data processing agreement (DPA) available on request

## 10. Plugin removal

When you uninstall the plugin:
- `oc_setting` rows under code `module_dowaba_ai` deleted
- `dowaba_audit` table **NOT** automatically dropped (data preservation by default)
- To purge audit data: `DROP TABLE oc_dowaba_audit` manually

Dowaba account deletion (separate): https://dowaba.com/account/delete

---

<a name="turkce"></a>

## 🇹🇷 Türkçe — Gizlilik Politikası

### 1. Veri Sorumlusu

- **Plugin operatörü**: Mağaza sahibi (sen)
- **Veri işleyen**: Dowaba (Aydın Acar, Türkiye)
- **İletişim**: https://dowaba.com/destek

### 2. Hangi veriler nereye gidiyor?

Plugin Dowaba ile OpenCart arasında bir REST API köprüsü. Müşteri WhatsApp/IG'den ürün sorduğunda Dowaba mağazadan **sadece o sorgu için gerekli** veriyi çeker:
- Ürün arama: ad/SKU + sonuçlar (max 50 ürün)
- Sipariş sorgu: order_id + email match (KVKK)
- Müşteri sorgu: SADECE doğrulanmış müşteri (phone match)

Müşteri verisi mağaza dışına otomatik gönderilmez — Dowaba çağrı yaptığında, çağrı bazında işlenir.

### 3. Veri saklama süreleri

| Veri | Süre | Nerede |
|---|---|---|
| Audit log | 30 gün (ayarlanabilir) | OpenCart DB (lokal) |
| Sipariş ön izleme cache | 5 dakika | OpenCart cache |
| API key hash | Manuel sıfırlamaya kadar | OpenCart `oc_setting` |
| Müşteri mesajları | Dowaba retention politikası | Dowaba sunucuları (Hetzner, Almanya) |

### 4. Müşteri Hakları (KVKK Madde 11)

- Veriye erişim talebi
- Silme talebi ("unutulma hakkı")
- Düzeltme talebi
- İşleme itiraz

Talep adresi: https://dowaba.com/destek (24 saat yanıt).

### 5. Güvenlik

- sha256 hash'li bearer token
- IP whitelist (Dowaba prod IP'leri)
- Scope guard (yazma izni varsayılan kapalı)
- TLS/HTTPS şifreleme
- SQL injection + SSRF koruması
- 30 gün audit log

### 6. KVKK Uyumluluk

- Madde 5/2: meşru menfaat + açık rıza
- Madde 9: sınır ötesi aktarım (AI inference için) açık rıza ile
- Madde 11: hak kullanımı https://dowaba.com/destek üzerinden
- KVKK Aydınlatma Metni: dowaba.com/kvkk

### 7. Plugin kaldırma

Plugin uninstall edildiğinde:
- `oc_setting` ayarları silinir
- `dowaba_audit` tablosu manuel silinmeli (veri korumayı amaçlar)

Dowaba hesabı silme: https://dowaba.com/account/delete

---

## Yasal Notlar

This privacy policy may be updated. Current version always at: https://github.com/acr-yazilim/dowaba-plugins/blob/main/opencart/marketing/PRIVACY.md

For binding legal terms, see Dowaba's main Privacy Policy at https://dowaba.com/privacy (Turkish + English).

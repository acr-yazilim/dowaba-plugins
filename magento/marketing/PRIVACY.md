# Privacy Policy — Dowaba AI Connector for Magento 2

> 🇹🇷 Türkçe + 🇬🇧 English · KVKK + GDPR uyumlu

Bu modül, Magento mağazanız ile **Dowaba AI** platformu arasında bir köprüdür. Aşağıda hangi verinin işlendiği, nereye gittiği ve nasıl korunduğu açıklanır.

## 1. İşlenen veriler (TR)

Modül, yalnızca Dowaba AI bir müşteri sorusunu yanıtlarken ve **yalnızca ilgili fonksiyon çağrıldığında** şu verilere erişir:

| Veri | Hangi fonksiyon | Amaç |
|---|---|---|
| Ürün adı, fiyat, stok, açıklama, görsel | product_search / detail / compare / stock | Müşteriye ürün bilgisi |
| Kategori adları | category_list | Gezinme |
| Sipariş durumu, tutarı, tarihi | order_status | **Yalnızca e-posta eşleşmesiyle** sipariş takibi |
| Müşteri adı, e-posta, telefon, son 5 sipariş | customer_lookup | Kişiselleştirilmiş yanıt (doğrulanmış müşteri) |
| Sipariş kalemleri + teslimat bilgisi | order_preview / confirm | Müşteri onayıyla sipariş oluşturma |

**Erişilmeyen veriler:** ödeme kartı bilgileri, parolalar, yönetici hesapları, başka müşterilerin verileri.

## 2. Veri akışı

```
Müşteri kanalı (WhatsApp/IG/Mail/Voice)
   → Dowaba AI (dowaba.com)
   → HTTPS + Bearer token ile mağazanıza istek
   → Modül: auth + scope + audit → Magento DB
   → JSON yanıt → Dowaba AI → müşteriye cevap
```

- Veri mağazanız ile Dowaba arasında **HTTPS** üzerinden, **Bearer token (sha256)** ile şifreli kimlik doğrulamayla taşınır.
- Modül mağaza verisini **dışarıda saklamaz**; her istek anlık çalışır.
- Dowaba tarafında veri işleme: [dowaba.com gizlilik politikası](https://dowaba.com).

## 3. Denetim günlüğü (audit log)

Her API isteği `dowaba_ai_audit` tablosuna yazılır: fonksiyon adı, IP, durum kodu, süre, hata. **Kişisel veri (müşteri adı/e-posta) audit'e yazılmaz.** Varsayılan 30 gün sonra otomatik silinir.

## 4. Güvenlik önlemleri

- Bearer token sha256 hash olarak saklanır (plain anahtar DB'de yoktur).
- IP whitelist ile erişim kısıtlanabilir.
- `write` izni (sipariş oluşturma) varsayılan kapalıdır; bilinçli açılır.
- Sipariş oluşturma 2 adımlı müşteri onayı gerektirir (one-shot, replay korumalı).

## 5. KVKK / GDPR

- Müşteri sipariş/profil sorgusu **yalnızca e-posta veya telefon eşleşmesiyle** döner — başkasının verisi sızmaz.
- Veri sorumlusu mağaza sahibidir; Dowaba veri işleyendir. Sözleşme/DPA için: [dowaba.com/destek](https://dowaba.com/destek).
- Modülü kaldırırsanız audit tablosu manuel silinebilir (`DROP TABLE dowaba_ai_audit`).

---

## English summary

This module bridges your Magento store with the **Dowaba AI** platform. It accesses product/category/order/customer data **only when the relevant AI function is invoked** to answer a customer. It never accesses payment card data, passwords, or admin accounts. Data travels over **HTTPS with sha256 Bearer authentication** and is not stored outside your store. Order/customer lookups require an **email or phone match** (no cross-customer leakage). An audit log (function, IP, status — **no personal data**) is kept for 30 days by default. The `write` scope (order creation) is **off by default**. Data controller: the store owner; data processor: Dowaba — see [dowaba.com](https://dowaba.com).

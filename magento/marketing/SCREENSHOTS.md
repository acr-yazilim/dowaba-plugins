# Screenshots Guide — Dowaba AI Connector (Magento)

Marketplace + README için hazırlanacak görseller. Gerçek ekran görüntüsü (mock değil) — Marketplace review şartı.

## Zorunlu (en az 3)

1. **Setup wizard — Adım 1+2** — Admin → Dowaba AI → Setup & Settings. API key üretildi + manifest URL kopyalanabilir halde. (Mor hero başlık görünür.)
2. **Setup wizard — Aktivasyon & İzinler** — read/write toggle'ları + IP whitelist alanı.
3. **Bağlantı testi başarılı** — yeşil ✅ "schema 1.0 · 10 functions".
4. **Audit log paneli** — birkaç gerçek API çağrısı (mgm_product_search 200, mgm_order_status 200...).
5. **WhatsApp konuşması** — müşteri "iPhone var mı?" → AI mağazadan canlı yanıt (Dowaba inbox veya telefon ekran görüntüsü).
6. **Oluşan sipariş** — Magento Admin → Sales → Orders → Dowaba AI üzerinden gelen guest order (comment'te preview_id).

## Logo / banner

- **Logo:** 200×200 PNG, mor (#5b3df5) Dowaba marka rengi, "D" veya Dowaba logosu.
- **Banner:** Marketplace 1200×400 önerir — sol metin "AI commerce on WhatsApp", sağ konuşma balonu mockup.

## Üretim yöntemi (LESSONS_LEARNED'den)

```bash
# HTML → PNG (Chrome headless en kaliteli)
chrome --headless --window-size=1200,400 --screenshot=banner.png file://banner.html
```

ImageMagick gradient zayıf — kompleks tasarımda Chrome tercih et. OpenCart banner HTML template'leri (`opencart/marketing/banner-710x380.html`) renk/marka değiştirilerek reuse edilebilir.

## Boyut özeti

| Görsel | Boyut |
|---|---|
| Logo | 200×200 |
| Ekran görüntüsü | min 1280×800 (retina tercih) |
| Banner | 1200×400 |

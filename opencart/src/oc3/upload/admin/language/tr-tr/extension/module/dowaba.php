<?php
// Heading
$_['heading_title']            = 'Dowaba AI Entegrasyonu';

// Text
$_['text_extension']           = 'Eklentiler';
$_['text_success']             = 'Başarılı: Dowaba AI modülü güncellendi!';
$_['text_edit']                = 'Dowaba AI Entegrasyonunu Düzenle';

// Wizard adımları
$_['text_step_1_title']        = 'Adım 1 — API Anahtarı';
$_['text_step_1_desc']         = 'Rastgele bir API anahtarı üret. Dowaba her function çağrısında bu anahtarı Authorization header\'ında gönderecek.';
$_['text_step_2_title']        = 'Adım 2 — Manifest URL';
$_['text_step_2_desc']         = 'Bu URL\'i Dowaba paneline → Bundle Import\'a yapıştır. Dowaba bir kez fetch eder ve 10 AI function\'ını otomatik oluşturur.';
$_['text_step_3_title']        = 'Adım 3 — IP Whitelist (opsiyonel)';
$_['text_step_3_desc']         = 'Virgülle ayrılmış izinli IP listesi. Boşsa kısıtlama yok. Dowaba prod IP\'leri: 178.105.68.170, 49.13.120.112';
$_['text_step_4_title']        = 'Adım 4 — İzin Kapsamı (Scope)';
$_['text_step_4_desc']         = 'AI\'ın hangi function kategorilerini çağırabileceğini seç.';
$_['text_step_5_title']        = 'Adım 5 — Bağlantı Testi';
$_['text_step_5_desc']         = 'Manifest URL\'in erişilebilir mi ve geçerli JSON döndürüyor mu doğrula.';

// Entry labels
$_['entry_status']             = 'Durum';
$_['entry_api_key']            = 'API Anahtarı';
$_['entry_api_key_prefix']     = 'Mevcut anahtar';
$_['entry_ip_whitelist']       = 'IP Whitelist';
$_['entry_scope_read']         = 'Okuma izni (ürün, sipariş, müşteri)';
$_['entry_scope_write']        = 'Yazma izni (sipariş oluşturma)';
$_['entry_audit_retention']    = 'Audit log saklama (gün)';

// Yardım
$_['help_api_key']             = 'Plain anahtar sadece BİR KERE gösterilir — Dowaba paneline kopyalamayı unutma. Sonra OpenCart\'ta sadece hash kalır.';
$_['help_scope_write']         = 'DİKKAT: Yazma izni AI\'ın müşteri adına sipariş oluşturmasına olanak tanır. SADECE müşteri onay akışıyla kullan (opc_order_preview → opc_order_confirm).';
$_['help_manifest_url']        = 'Bu URL, Dowaba\'nın içe aktardığı .well-known/dowaba-bundle.json manifest dosyasını sunar.';

// Butonlar
$_['button_save']              = 'Kaydet';
$_['button_cancel']            = 'İptal';
$_['button_regenerate']        = 'API Anahtarı Yenile';
$_['button_copy']              = 'Kopyala';
$_['button_test']              = 'Bağlantı testi';

// Audit Log
$_['text_audit_log_title']     = 'Audit Log — Son API istekleri';
$_['text_audit_log_desc']      = 'Dowaba\'dan gelen son istekler. Function slug veya status code ile filtreleyebilirsin (4xx/5xx = hata).';
$_['column_when']              = 'Tarih';
$_['column_function']          = 'Function';
$_['column_ip']                = 'IP';
$_['column_status']            = 'Durum';
$_['column_duration']          = 'ms';
$_['column_error']             = 'Hata';
$_['text_no_audit_rows']       = 'Henüz hiç istek loglanmadı. Dowaba bir function çağırınca burada satır göreceksin.';
$_['button_refresh']           = 'Yenile';

// Hata
$_['error_permission']         = 'Uyarı: Dowaba AI modülünü düzenleme izniniz yok!';
$_['error_invalid_ip']         = 'Geçersiz IP adresi: %s';

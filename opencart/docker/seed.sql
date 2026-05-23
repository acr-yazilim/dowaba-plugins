-- ============================================================
-- Dowaba × OpenCart — Lokal test seed data
-- ============================================================
-- 10 dummy product (iPhone, Samsung, Xiaomi telefon kategorisinde)
--  3 dummy order (varied status)
--
-- NOTE: OpenCart 4.x official Docker image kendi schema'sını
-- yarattıktan SONRA bu seed.sql çalıştırılır (entrypoint sırası).
-- Eğer OpenCart hala kurulum yapıyorsa, schema yoksa bu satırlar
-- silent skip eder (IF EXISTS guard).
--
-- Faz 1 sonu doldurulacak — şu an placeholder.
-- ============================================================

-- TODO Faz 1: Aşağıdaki INSERT'ler eklenecek
-- INSERT INTO oc_product (...) VALUES ...
-- INSERT INTO oc_order (...) VALUES ...

SELECT 'seed.sql placeholder — Faz 1 sonu doldurulacak' AS info;

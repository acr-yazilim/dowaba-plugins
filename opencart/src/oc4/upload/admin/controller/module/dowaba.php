<?php
namespace Opencart\Admin\Controller\Extension\DowabaAi\Module;

/**
 * Dowaba AI Integration — Admin Controller
 *
 * OpenCart 4.x extension. Setup wizard'ı 5 adım:
 *   1. API key üret + kopyala
 *   2. Manifest URL göster + kopyala
 *   3. IP whitelist (opsiyonel)
 *   4. Scope toggle'ları (read default ON, write default OFF)
 *   5. Bağlantı testi
 *
 * Route: extension/dowaba_ai/module/dowaba
 * Permission slug: extension/dowaba_ai/module/dowaba
 */
class Dowaba extends \Opencart\System\Engine\Controller {

    /**
     * Setting key prefix. Tüm ayarlar oc_setting tablosunda `module_dowaba_ai_<key>` formatında saklanır.
     * OC4 convention: `module_<code>_<key>` (extension type + extension code + setting key).
     */
    private string $settingPrefix = 'module_dowaba_ai_';

    /**
     * Default settings — fresh install veya reset durumunda.
     */
    private array $defaults = [
        'status'            => 0,        // module aktif/pasif
        'api_key_hash'      => '',       // sha256(plain_key), plain saklanmaz
        'api_key_prefix'    => '',       // ilk 12 char (UI gösterimi için)
        'api_key_last_used' => null,     // ISO datetime
        'ip_whitelist'      => '',       // virgüllü liste, boş = kısıtlama yok
        'scope_read'        => 1,        // ürün/sipariş/müşteri okuma izni
        'scope_write'       => 0,        // sipariş oluşturma izni (default kapalı)
        'audit_retention_days' => 30,
    ];

    public function index(): void {
        $this->load->language('extension/dowaba_ai/module/dowaba');

        $this->document->setTitle($this->language->get('heading_title'));

        // Permission check
        if (!$this->user->hasPermission('modify', 'extension/dowaba_ai/module/dowaba')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        }

        // POST → settings kaydet
        if (($this->request->server['REQUEST_METHOD'] === 'POST') && $this->validate()) {
            $this->load->model('setting/setting');

            $settings = [];
            foreach (array_keys($this->defaults) as $key) {
                $postKey = $this->settingPrefix . $key;
                if (isset($this->request->post[$postKey])) {
                    $settings[$postKey] = $this->request->post[$postKey];
                }
            }
            $this->model_setting_setting->editSetting('module_dowaba_ai', $settings);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            );
        }

        $data = $this->buildViewData();
        $this->response->setOutput($this->load->view('extension/dowaba_ai/module/dowaba', $data));
    }

    /**
     * Install hook — extension activate edildiğinde çağrılır.
     * dowaba_audit tablosunu oluşturur (Faz 2'de Model'e taşınacak).
     */
    public function install(): void {
        $this->load->model('extension/dowaba_ai/module/dowaba');
        $this->model_extension_dowaba_ai_module_dowaba->install();
    }

    /**
     * Uninstall hook — extension deactivate edildiğinde.
     * Şu an audit tablosu DROP edilmiyor (data preservation). Faz 4'te opsiyonel flag eklenir.
     */
    public function uninstall(): void {
        $this->load->model('extension/dowaba_ai/module/dowaba');
        $this->model_extension_dowaba_ai_module_dowaba->uninstall();
    }

    /**
     * AJAX endpoint: Yeni API key üret.
     * Plain key sadece bu HTTP yanıtında döner. Sonrasında DB'de sadece sha256 hash + prefix saklanır.
     * Route: extension/dowaba_ai/module/dowaba|regenerateKey
     */
    public function regenerateKey(): void {
        $this->load->language('extension/dowaba_ai/module/dowaba');
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('modify', 'extension/dowaba_ai/module/dowaba')) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $this->language->get('error_permission'),
            ]));
            return;
        }

        // Generate: opc_ + 64 hex chars
        $plainKey = 'opc_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $plainKey);
        $prefix = substr($plainKey, 0, 12); // 'opc_xxxxxxxx' for UI display

        $this->load->model('setting/setting');
        // OC4 + PHP 8 strict types — DB::escape() null kabul etmiyor.
        // api_key_last_used'i reset için boş string ver ('' = "henüz kullanılmadı").
        $this->model_setting_setting->editSetting('module_dowaba_ai', [
            $this->settingPrefix . 'api_key_hash'      => $hash,
            $this->settingPrefix . 'api_key_prefix'    => $prefix,
            $this->settingPrefix . 'api_key_last_used' => '',
        ]);

        $this->response->setOutput(json_encode([
            'success'   => true,
            'plain_key' => $plainKey,  // SADECE BU YANITTA. Sonra ulaşılamaz.
            'prefix'    => $prefix,
        ]));
    }

    /**
     * AJAX endpoint: Bağlantı testi — kendi manifest URL'ini fetch et + JSON valid mi check.
     * Route: extension/dowaba_ai/module/dowaba|testConnection
     */
    public function testConnection(): void {
        $this->load->language('extension/dowaba_ai/module/dowaba');
        $this->response->addHeader('Content-Type: application/json');

        $manifestUrl = $this->buildManifestUrl();

        $ch = curl_init($manifestUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => 'Connection error: ' . $error,
                'url'     => $manifestUrl,
            ]));
            return;
        }

        if ($httpCode !== 200) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => 'HTTP ' . $httpCode,
                'url'     => $manifestUrl,
            ]));
            return;
        }

        $manifest = json_decode($body, true);
        if (!is_array($manifest) || !isset($manifest['schema_version'])) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => 'Invalid manifest JSON',
                'url'     => $manifestUrl,
            ]));
            return;
        }

        $this->response->setOutput(json_encode([
            'success'        => true,
            'url'            => $manifestUrl,
            'schema_version' => $manifest['schema_version'],
            'function_count' => count($manifest['functions'] ?? []),
        ]));
    }

    /**
     * AJAX endpoint: Audit log — son N kayıt + opsiyonel filter.
     * Route: extension/dowaba_ai/module/dowaba|auditLog
     */
    public function auditLog(): void {
        $this->load->language('extension/dowaba_ai/module/dowaba');
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('access', 'extension/dowaba_ai/module/dowaba')) {
            $this->response->setOutput(json_encode([
                'success' => false,
                'error'   => $this->language->get('error_permission'),
            ]));
            return;
        }

        $limit = max(10, min(500, (int) ($this->request->get['limit'] ?? 100)));
        $functionSlug = trim((string) ($this->request->get['function_slug'] ?? '')) ?: null;
        $statusFilter = isset($this->request->get['status']) ? (int) $this->request->get['status'] : null;

        $this->load->model('extension/dowaba_ai/module/dowaba');
        $rows = $this->model_extension_dowaba_ai_module_dowaba->getAuditLog($limit, $functionSlug, $statusFilter);

        $this->response->setOutput(json_encode([
            'success' => true,
            'count'   => count($rows),
            'rows'    => array_map(fn($r) => [
                'audit_id'      => (int) $r['audit_id'],
                'function_slug' => $r['function_slug'],
                'request_ip'    => $r['request_ip'],
                'status_code'   => (int) $r['status_code'],
                'duration_ms'   => (int) $r['duration_ms'],
                'error_message' => $r['error_message'],
                'created_at'    => $r['created_at'],
            ], $rows),
        ]));
    }

    // ---------------------------------------------------------------- helpers

    /**
     * View'a aktarılan veri seti.
     */
    private function buildViewData(): array {
        $this->load->model('setting/setting');

        $data = [];

        // Heading + breadcrumbs + form action URLs
        $data['heading_title']    = $this->language->get('heading_title');
        $data['user_token']       = $this->session->data['user_token'];
        $data['action']           = $this->url->link('extension/dowaba_ai/module/dowaba', 'user_token=' . $data['user_token'], true);
        $data['cancel']           = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=module', true);
        // Breadcrumb URL'leri — OC4 Twig'de url() function tanımlı DEĞİL, controller'dan string olarak veriyoruz
        $data['dashboard_url']    = $this->url->link('common/dashboard', 'user_token=' . $data['user_token'], true);
        $data['extension_url']    = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=module', true);
        // OpenCart'ın url->link() metodu URL'i `&amp;` HTML-encoded döndürür (href attribute için).
        // JS fetch'te bu string literal `&amp;` olur → user_token parse edilemez → admin login redirect → JSON parse fail.
        // html_entity_decode ile `&amp;` → `&` geri döndürüyoruz (JS context için).
        $data['regenerate_url']   = html_entity_decode($this->url->link('extension/dowaba_ai/module/dowaba.regenerateKey', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['test_url']         = html_entity_decode($this->url->link('extension/dowaba_ai/module/dowaba.testConnection', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['audit_log_url']    = html_entity_decode($this->url->link('extension/dowaba_ai/module/dowaba.auditLog', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['manifest_url']     = $this->buildManifestUrl();

        // Mevcut ayarlar
        foreach ($this->defaults as $key => $default) {
            $settingKey = $this->settingPrefix . $key;
            $data[$settingKey] = $this->config->get($settingKey) ?? $default;
        }

        // Header / footer / column_left
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        return $data;
    }

    /**
     * Manifest URL'ini OpenCart'ın config_url'inden inşa eder.
     * Storefront base + ?route=extension/dowaba_ai/manifest
     */
    private function buildManifestUrl(): string {
        // 3 katman fallback — config_url admin'de set edilmemiş olabilir, HTTP_CATALOG constant'ı admin context'te tanımlı.
        $configUrl = (string)$this->config->get('config_url');
        $base = $configUrl !== '' ? $configUrl : (defined('HTTP_CATALOG') ? HTTP_CATALOG : '');
        return rtrim($base, '/') . '/index.php?route=extension/dowaba_ai/manifest';
    }

    /**
     * Form validation. Şu an minimum: permission check.
     */
    private function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/dowaba_ai/module/dowaba')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
            return false;
        }

        // IP whitelist sanity check
        $ipWhitelist = trim($this->request->post[$this->settingPrefix . 'ip_whitelist'] ?? '');
        if ($ipWhitelist !== '') {
            $ips = array_map('trim', explode(',', $ipWhitelist));
            foreach ($ips as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    $this->session->data['error_warning'] = sprintf($this->language->get('error_invalid_ip'), $ip);
                    return false;
                }
            }
        }

        return true;
    }
}

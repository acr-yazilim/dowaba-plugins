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
        $this->model_setting_setting->editSetting('module_dowaba_ai', [
            $this->settingPrefix . 'api_key_hash'      => $hash,
            $this->settingPrefix . 'api_key_prefix'    => $prefix,
            $this->settingPrefix . 'api_key_last_used' => null,
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
        $data['regenerate_url']   = $this->url->link('extension/dowaba_ai/module/dowaba.regenerateKey', 'user_token=' . $data['user_token'], true);
        $data['test_url']         = $this->url->link('extension/dowaba_ai/module/dowaba.testConnection', 'user_token=' . $data['user_token'], true);
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
        $base = rtrim($this->config->get('config_url'), '/');
        return $base . '/index.php?route=extension/dowaba_ai/manifest';
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

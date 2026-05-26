<?php
/**
 * Dowaba AI Integration — Admin Controller (OpenCart 3.x)
 *
 * OC3 farkları (OC4'ten):
 *   - Namespace yok (global PHP class)
 *   - Class adlandırma: Controller + camelcase(route) — case-insensitive
 *     extension/module/dowaba → ControllerExtensionModuleDowaba
 *   - Library autoload yok; manuel require_once
 *   - View'lar .twig (OC4 ile uyumlu, büyük kolaylık)
 *   - random_bytes / hash_equals PHP 7+ ile mevcut
 */
class ControllerExtensionModuleDowaba extends Controller {

    private $settingPrefix = 'module_dowaba_ai_';

    private $defaults = array(
        'status'                => 0,
        'api_key_hash'          => '',
        'api_key_prefix'        => '',
        'api_key_last_used'     => null,
        'ip_whitelist'          => '',
        'scope_read'            => 1,
        'scope_write'           => 0,
        'audit_retention_days'  => 30,
    );

    private $error = array();

    public function index() {
        $this->load->language('extension/module/dowaba');
        $this->document->setTitle($this->language->get('heading_title'));

        // Permission check
        if (!$this->user->hasPermission('modify', 'extension/module/dowaba')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // POST → settings kaydet
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');

            $settings = array();
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
            return;
        }

        $data = $this->buildViewData();
        $this->response->setOutput($this->load->view('extension/module/dowaba', $data));
    }

    /**
     * AJAX: random API key üret + sha256 hash + prefix sakla.
     * Route: extension/module/dowaba/regenerateKey
     */
    public function regenerateKey() {
        $this->load->language('extension/module/dowaba');
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('modify', 'extension/module/dowaba')) {
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error'   => $this->language->get('error_permission'),
            )));
            return;
        }

        $plainKey = 'opc_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $plainKey);
        $prefix = substr($plainKey, 0, 12);

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_dowaba_ai', array(
            $this->settingPrefix . 'api_key_hash'      => $hash,
            $this->settingPrefix . 'api_key_prefix'    => $prefix,
            $this->settingPrefix . 'api_key_last_used' => null,
        ));

        $this->response->setOutput(json_encode(array(
            'success'   => true,
            'plain_key' => $plainKey,
            'prefix'    => $prefix,
        )));
    }

    /**
     * AJAX: Kendi manifest URL'imizi fetch et + JSON validate.
     * Route: extension/module/dowaba/testConnection
     */
    public function testConnection() {
        $this->load->language('extension/module/dowaba');
        $this->response->addHeader('Content-Type: application/json');

        $manifestUrl = $this->buildManifestUrl();

        $ch = curl_init($manifestUrl);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => array('Accept: application/json'),
        ));
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error'   => 'Connection error: ' . $error,
                'url'     => $manifestUrl,
            )));
            return;
        }

        if ($httpCode !== 200) {
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error'   => 'HTTP ' . $httpCode,
                'url'     => $manifestUrl,
            )));
            return;
        }

        $manifest = json_decode($body, true);
        if (!is_array($manifest) || !isset($manifest['schema_version'])) {
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error'   => 'Invalid manifest JSON',
                'url'     => $manifestUrl,
            )));
            return;
        }

        $this->response->setOutput(json_encode(array(
            'success'        => true,
            'url'            => $manifestUrl,
            'schema_version' => $manifest['schema_version'],
            'function_count' => count(isset($manifest['functions']) ? $manifest['functions'] : array()),
        )));
    }

    /**
     * AJAX: Audit log JSON endpoint.
     * Route: extension/module/dowaba/auditLog
     */
    public function auditLog() {
        $this->load->language('extension/module/dowaba');
        $this->response->addHeader('Content-Type: application/json');

        if (!$this->user->hasPermission('access', 'extension/module/dowaba')) {
            $this->response->setOutput(json_encode(array(
                'success' => false,
                'error'   => $this->language->get('error_permission'),
            )));
            return;
        }

        $limit = max(10, min(500, (int)(isset($this->request->get['limit']) ? $this->request->get['limit'] : 100)));
        $functionSlug = trim((string)(isset($this->request->get['function_slug']) ? $this->request->get['function_slug'] : ''));
        $statusFilter = isset($this->request->get['status']) ? (int)$this->request->get['status'] : null;

        $this->load->model('extension/module/dowaba');
        $rows = $this->model_extension_module_dowaba->getAuditLog($limit, $functionSlug ?: null, $statusFilter);

        $this->response->setOutput(json_encode(array(
            'success' => true,
            'count'   => count($rows),
            'rows'    => array_map(function ($r) {
                return array(
                    'audit_id'      => (int)$r['audit_id'],
                    'function_slug' => $r['function_slug'],
                    'request_ip'    => $r['request_ip'],
                    'status_code'   => (int)$r['status_code'],
                    'duration_ms'   => (int)$r['duration_ms'],
                    'error_message' => $r['error_message'],
                    'created_at'    => $r['created_at'],
                );
            }, $rows),
        )));
    }

    /**
     * OC3 extension install/uninstall hook'ları (extension marketplace install butonu)
     */
    public function install() {
        $this->load->model('extension/module/dowaba');
        $this->model_extension_module_dowaba->install();
    }

    public function uninstall() {
        $this->load->model('extension/module/dowaba');
        $this->model_extension_module_dowaba->uninstall();
    }

    // ---------------------------------------------------------------- helpers

    private function buildViewData() {
        $this->load->model('setting/setting');

        $data = array();
        $data['heading_title']  = $this->language->get('heading_title');
        $data['user_token']     = $this->session->data['user_token'];
        $data['action']         = $this->url->link('extension/module/dowaba', 'user_token=' . $data['user_token'], true);
        $data['cancel']         = $this->url->link('marketplace/extension', 'user_token=' . $data['user_token'] . '&type=module', true);
        // OpenCart'ın url->link() metodu URL'i `&amp;` HTML-encoded döndürür (href attribute için).
        // JS fetch'te bu string literal `&amp;` olur → user_token parse edilemez → admin login redirect → JSON parse fail.
        // html_entity_decode ile `&amp;` → `&` geri döndürüyoruz (JS context için).
        $data['regenerate_url'] = html_entity_decode($this->url->link('extension/module/dowaba/regenerateKey', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['test_url']       = html_entity_decode($this->url->link('extension/module/dowaba/testConnection', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['audit_log_url']  = html_entity_decode($this->url->link('extension/module/dowaba/auditLog', 'user_token=' . $data['user_token'], true), ENT_QUOTES, 'UTF-8');
        $data['manifest_url']   = $this->buildManifestUrl();

        // Error + success
        $data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // Settings
        foreach ($this->defaults as $key => $default) {
            $settingKey = $this->settingPrefix . $key;
            $val = $this->config->get($settingKey);
            $data[$settingKey] = ($val !== null && $val !== '') ? $val : $default;
        }

        // Common header / column_left / footer
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        return $data;
    }

    private function buildManifestUrl() {
        // OC3'te HTTP_CATALOG storefront URL'i — config_url admin URL'i!
        $base = rtrim(defined('HTTP_CATALOG') ? HTTP_CATALOG : $this->config->get('config_url'), '/');
        return $base . '/index.php?route=extension/dowaba_ai/manifest';
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/dowaba')) {
            $this->error['warning'] = $this->language->get('error_permission');
            return false;
        }

        $ipWhitelist = trim(isset($this->request->post[$this->settingPrefix . 'ip_whitelist'])
            ? $this->request->post[$this->settingPrefix . 'ip_whitelist']
            : '');
        if ($ipWhitelist !== '') {
            $ips = array_map('trim', explode(',', $ipWhitelist));
            foreach ($ips as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    $this->error['warning'] = sprintf($this->language->get('error_invalid_ip'), $ip);
                    return false;
                }
            }
        }

        return true;
    }
}

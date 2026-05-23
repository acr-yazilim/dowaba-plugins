<?php
/**
 * DoWaba AI Integration for PrestaShop
 *
 * Module main class. PrestaShop 8.x conventions.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Dowaba_Ai extends Module
{
    const TABLE_AUDIT = 'dowaba_audit';
    const PREVIEW_TTL = 300; // 5 dakika

    public function __construct()
    {
        $this->name = 'dowaba_ai';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = 'Aydın Acar (DoWaba)';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DoWaba AI — Sell on WhatsApp, Instagram & TikTok');
        $this->description = $this->l('AI chatbot for your PrestaShop store. Sell directly on WhatsApp, Instagram DM, and TikTok. 24/7, 30+ languages, customer-confirmed orders.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? Audit log table will be dropped.');

        if (!Configuration::get('DOWABA_AI_STATUS')) {
            $this->warning = $this->l('Plugin is installed but disabled. Configure it in module settings.');
        }
    }

    public function install()
    {
        if (!parent::install()) return false;

        // Settings defaults
        Configuration::updateValue('DOWABA_AI_STATUS', 0);
        Configuration::updateValue('DOWABA_AI_SCOPE_READ', 1);
        Configuration::updateValue('DOWABA_AI_SCOPE_WRITE', 0);
        Configuration::updateValue('DOWABA_AI_AUDIT_RETENTION_DAYS', 30);
        Configuration::updateValue('DOWABA_AI_IP_WHITELIST', '');
        Configuration::updateValue('DOWABA_AI_API_KEY_HASH', '');
        Configuration::updateValue('DOWABA_AI_API_KEY_PREFIX', '');

        // Audit table
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_AUDIT . '` (
            audit_id      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            function_slug VARCHAR(64) NOT NULL,
            request_ip    VARCHAR(45) NOT NULL,
            status_code   SMALLINT(3) NOT NULL,
            duration_ms   INT(11) NOT NULL DEFAULT 0,
            error_message TEXT NULL,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (audit_id),
            INDEX idx_created_at   (created_at),
            INDEX idx_function_slug(function_slug),
            INDEX idx_status_code  (status_code)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return Db::getInstance()->execute($sql);
    }

    public function uninstall()
    {
        $keys = [
            'DOWABA_AI_STATUS', 'DOWABA_AI_SCOPE_READ', 'DOWABA_AI_SCOPE_WRITE',
            'DOWABA_AI_AUDIT_RETENTION_DAYS', 'DOWABA_AI_IP_WHITELIST',
            'DOWABA_AI_API_KEY_HASH', 'DOWABA_AI_API_KEY_PREFIX',
            'DOWABA_AI_API_KEY_LAST_USED', 'DOWABA_AI_MANIFEST_BASE_URL',
        ];
        foreach ($keys as $k) Configuration::deleteByName($k);

        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE_AUDIT . '`');

        return parent::uninstall();
    }

    /**
     * Admin config page — accessed via "Configure" link in module list.
     */
    public function getContent()
    {
        $output = '';

        // POST save
        if (Tools::isSubmit('submit_dowaba_ai')) {
            Configuration::updateValue('DOWABA_AI_STATUS',          (int) Tools::getValue('DOWABA_AI_STATUS'));
            Configuration::updateValue('DOWABA_AI_SCOPE_READ',      (int) Tools::getValue('DOWABA_AI_SCOPE_READ'));
            Configuration::updateValue('DOWABA_AI_SCOPE_WRITE',     (int) Tools::getValue('DOWABA_AI_SCOPE_WRITE'));
            Configuration::updateValue('DOWABA_AI_AUDIT_RETENTION_DAYS', max(1, (int) Tools::getValue('DOWABA_AI_AUDIT_RETENTION_DAYS')));
            Configuration::updateValue('DOWABA_AI_IP_WHITELIST',    pSQL(Tools::getValue('DOWABA_AI_IP_WHITELIST')));
            Configuration::updateValue('DOWABA_AI_MANIFEST_BASE_URL', pSQL(Tools::getValue('DOWABA_AI_MANIFEST_BASE_URL')));
            $output .= $this->displayConfirmation($this->l('Settings saved.'));
        }

        // Regenerate key action
        if (Tools::isSubmit('regenerate_key')) {
            require_once __DIR__ . '/classes/Auth.php';
            $plain_key = DowabaAuth::generateKey();
            $output .= $this->displayConfirmation($this->l('New API Key (save now, shown ONCE): ') . '<code>' . htmlspecialchars($plain_key) . '</code>');
        }

        return $output . $this->renderForm();
    }

    private function renderForm()
    {
        $manifest_url = $this->getManifestUrl();
        $api_key_prefix = (string) Configuration::get('DOWABA_AI_API_KEY_PREFIX');

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->l('DoWaba AI Settings'), 'icon' => 'icon-cog'],
                'description' =>
                    '<h4>📋 Manifest URL (Copy to DoWaba Bundle Import)</h4>' .
                    '<input type="text" value="' . htmlspecialchars($manifest_url) . '" readonly class="form-control" style="width:100%;margin-bottom:10px;">' .
                    '<h4>🔑 Current API Key</h4>' .
                    '<input type="text" value="' . htmlspecialchars($api_key_prefix ? $api_key_prefix . '... (hash stored)' : '— not generated yet —') . '" readonly class="form-control" style="width:100%;margin-bottom:10px;">' .
                    '<a href="' . AdminController::$currentIndex . '&configure=' . $this->name . '&regenerate_key=1&token=' . Tools::getAdminTokenLite('AdminModules') . '" class="btn btn-warning">🔑 Regenerate API Key</a>',
                'input' => [
                    [
                        'type' => 'switch', 'label' => $this->l('Module enabled'), 'name' => 'DOWABA_AI_STATUS', 'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch', 'label' => $this->l('Read scope (products, orders, customers)'), 'name' => 'DOWABA_AI_SCOPE_READ', 'is_bool' => true,
                        'values' => [
                            ['id' => 'r1', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'r0', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch', 'label' => $this->l('Write scope (AI can create orders)'), 'name' => 'DOWABA_AI_SCOPE_WRITE', 'is_bool' => true,
                        'desc' => $this->l('AI can place orders on behalf of customers — only with explicit 2-step confirmation.'),
                        'values' => [
                            ['id' => 'w1', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'w0', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('IP Whitelist (optional)'), 'name' => 'DOWABA_AI_IP_WHITELIST',
                        'desc' => $this->l('Comma-separated. Empty = no restriction. DoWaba prod IPs: 178.105.68.170, 49.13.120.112'),
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('Audit retention (days)'), 'name' => 'DOWABA_AI_AUDIT_RETENTION_DAYS',
                    ],
                    [
                        'type' => 'text', 'label' => $this->l('Manifest base URL override (optional)'), 'name' => 'DOWABA_AI_MANIFEST_BASE_URL',
                        'desc' => $this->l('Leave empty for auto-detect. Use this for Cloudflare tunnel / reverse proxy setups.'),
                    ],
                ],
                'submit' => ['title' => $this->l('Save'), 'class' => 'btn btn-primary'],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submit_dowaba_ai';
        $helper->fields_value = [
            'DOWABA_AI_STATUS' => Configuration::get('DOWABA_AI_STATUS'),
            'DOWABA_AI_SCOPE_READ' => Configuration::get('DOWABA_AI_SCOPE_READ'),
            'DOWABA_AI_SCOPE_WRITE' => Configuration::get('DOWABA_AI_SCOPE_WRITE'),
            'DOWABA_AI_IP_WHITELIST' => Configuration::get('DOWABA_AI_IP_WHITELIST'),
            'DOWABA_AI_AUDIT_RETENTION_DAYS' => Configuration::get('DOWABA_AI_AUDIT_RETENTION_DAYS'),
            'DOWABA_AI_MANIFEST_BASE_URL' => Configuration::get('DOWABA_AI_MANIFEST_BASE_URL'),
        ];

        return $helper->generateForm([$fields_form]);
    }

    private function getManifestUrl(): string
    {
        $base = rtrim(Tools::getShopDomainSsl(true), '/');
        return $base . '/index.php?fc=module&module=dowaba_ai&controller=manifest';
    }
}

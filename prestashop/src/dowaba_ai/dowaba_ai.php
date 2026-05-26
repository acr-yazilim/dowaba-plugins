<?php
/**
 * Dowaba AI Integration for PrestaShop — Module main class.
 *
 * PrestaShop 8.x conventions. Multi-channel AI chatbot connecting PrestaShop store
 * to WhatsApp, Instagram DM, TikTok via Dowaba SaaS.
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 *
 * @see      https://dowaba.com
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class Dowaba_Ai extends Module
{
    public const TABLE_AUDIT = 'dowaba_audit';
    public const TABLE_PREVIEW = 'dowaba_preview';
    public const PREVIEW_TTL = 300; // 5 dakika

    public function __construct()
    {
        $this->name = 'dowaba_ai';
        $this->tab = 'administration';
        $this->version = '0.2.7';
        $this->author = 'Aydın Acar (DoWaba)';
        $this->need_instance = 0;
        // Explicit min/max — addons.prestashop.com static analyzer requires literal strings,
        // not _PS_VERSION_ (which is evaluated at runtime and breaks static compatibility detection).
        $this->ps_versions_compliancy = ['min' => '1.7.0', 'max' => '9.1.99'];
        $this->bootstrap = true;
        // PrestaShop Addons marketplace product key — required for marketplace update notifications.
        // https://devdocs.prestashop-project.org/1.7/modules/sell/technical-tools/
        $this->module_key = '499fd42d03a2e28e29d5bf5067c38f71';

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
        if (!parent::install()) {
            return false;
        }

        // Settings defaults
        Configuration::updateValue('DOWABA_AI_STATUS', 0);
        Configuration::updateValue('DOWABA_AI_SCOPE_READ', 1);
        Configuration::updateValue('DOWABA_AI_SCOPE_WRITE', 0);
        Configuration::updateValue('DOWABA_AI_AUDIT_RETENTION_DAYS', 30);
        Configuration::updateValue('DOWABA_AI_IP_WHITELIST', '');
        Configuration::updateValue('DOWABA_AI_API_KEY_HASH', '');
        Configuration::updateValue('DOWABA_AI_API_KEY_PREFIX', '');

        $db = Db::getInstance();

        // Audit table
        $auditSql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_AUDIT . '` (
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

        // Preview table (DB-backed cache for order_preview → order_confirm flow)
        // v0.2.5: PrestaShop Cache::store kullanılıyordu, default install'da
        // ps_cache_enable=false olduğu için preview hep "expired" dönüyordu. DB-tabanlı.
        $previewSql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_PREVIEW . '` (
            preview_id VARCHAR(40) NOT NULL,
            payload    LONGTEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            PRIMARY KEY (preview_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return $db->execute($auditSql) && $db->execute($previewSql);
    }

    public function uninstall()
    {
        $keys = [
            'DOWABA_AI_STATUS', 'DOWABA_AI_SCOPE_READ', 'DOWABA_AI_SCOPE_WRITE',
            'DOWABA_AI_AUDIT_RETENTION_DAYS', 'DOWABA_AI_IP_WHITELIST',
            'DOWABA_AI_API_KEY_HASH', 'DOWABA_AI_API_KEY_PREFIX',
            'DOWABA_AI_API_KEY_LAST_USED', 'DOWABA_AI_MANIFEST_BASE_URL',
        ];
        foreach ($keys as $k) {
            Configuration::deleteByName($k);
        }

        $db = Db::getInstance();
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE_AUDIT . '`');
        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::TABLE_PREVIEW . '`');

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
            Configuration::updateValue('DOWABA_AI_STATUS', (int) Tools::getValue('DOWABA_AI_STATUS'));
            Configuration::updateValue('DOWABA_AI_SCOPE_READ', (int) Tools::getValue('DOWABA_AI_SCOPE_READ'));
            Configuration::updateValue('DOWABA_AI_SCOPE_WRITE', (int) Tools::getValue('DOWABA_AI_SCOPE_WRITE'));
            Configuration::updateValue('DOWABA_AI_AUDIT_RETENTION_DAYS', max(1, (int) Tools::getValue('DOWABA_AI_AUDIT_RETENTION_DAYS')));
            Configuration::updateValue('DOWABA_AI_IP_WHITELIST', pSQL(Tools::getValue('DOWABA_AI_IP_WHITELIST')));
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

        // 2026-05-26: Validator requirement — HTML must live in Smarty templates,
        // not PHP code. Header (manifest URL + API key + regenerate button) rendered
        // via views/templates/admin/configure_header.tpl.
        $this->context->smarty->assign([
            'dowaba_manifest_url' => $manifest_url,
            'dowaba_api_key_prefix' => $api_key_prefix,
            'dowaba_regenerate_url' => AdminController::$currentIndex . '&configure=' . $this->name . '&regenerate_key=1&token=' . Tools::getAdminTokenLite('AdminModules'),
        ]);
        $headerHtml = $this->display(__FILE__, 'views/templates/admin/configure_header.tpl');

        $fields_form = [
            'form' => [
                'legend' => ['title' => $this->l('DoWaba AI Settings'), 'icon' => 'icon-cog'],
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

        return $headerHtml . $helper->generateForm([$fields_form]);
    }

    private function getManifestUrl(): string
    {
        $base = rtrim(Tools::getShopDomainSsl(true), '/');

        return $base . '/index.php?fc=module&module=dowaba_ai&controller=manifest';
    }
}

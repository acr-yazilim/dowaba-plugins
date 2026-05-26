<?php
/**
 * Dowaba AI Integration for PrestaShop — Scope Guard.
 *
 * Per-function read/write scope enforcement. AI write operations (order create)
 * disabled by default — admin must explicitly enable via module config.
 * Protects against prompt-injection driven order creation.
 *
 * @author    Aydın Acar <support@dowaba.com>
 * @copyright 2024 Aydın Acar (DoWaba)
 * @license   https://opensource.org/licenses/MIT  MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DowabaScopeGuard
{
    public static $FUNCTION_SCOPES = [
        'psm_product_search' => 'read',
        'psm_product_detail' => 'read',
        'psm_product_compare' => 'read',
        'psm_stock_check' => 'read',
        'psm_category_list' => 'read',
        'psm_order_status' => 'read',
        'psm_customer_lookup' => 'read',
        'psm_cart_recover' => 'read',
        'psm_order_preview' => 'write',
        'psm_order_confirm' => 'write',
    ];

    public static function check(string $scope): array
    {
        if (!in_array($scope, ['read', 'write'], true)) {
            return ['allowed' => false, 'status' => 500, 'error' => 'Unknown scope: ' . $scope];
        }
        $enabled = (bool) Configuration::get('DOWABA_AI_SCOPE_' . strtoupper($scope));
        if (!$enabled) {
            return ['allowed' => false, 'status' => 403, 'error' => 'Scope "' . $scope . '" is disabled in module settings'];
        }

        return ['allowed' => true, 'status' => 200, 'error' => null];
    }
}

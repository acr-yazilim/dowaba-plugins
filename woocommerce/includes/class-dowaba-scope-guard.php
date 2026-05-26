<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

final class ScopeGuard {

    public const FUNCTION_SCOPES = [
        'wcm_product_search'  => 'read',
        'wcm_product_detail'  => 'read',
        'wcm_product_compare' => 'read',
        'wcm_stock_check'     => 'read',
        'wcm_category_list'   => 'read',
        'wcm_order_status'    => 'read',
        'wcm_customer_lookup' => 'read',
        'wcm_cart_recover'    => 'read',
        'wcm_order_preview'   => 'write',
        'wcm_order_confirm'   => 'write',
    ];

    /**
     * @return array{allowed: bool, status: int, error: string|null}
     */
    public static function check(string $scope): array {
        if (!in_array($scope, ['read', 'write'], true)) {
            return ['allowed' => false, 'status' => 500, 'error' => 'Unknown scope: ' . $scope];
        }
        $enabled = (bool) get_option('dowaba_ai_scope_' . $scope, $scope === 'read' ? 1 : 0);
        if (!$enabled) {
            return ['allowed' => false, 'status' => 403, 'error' => 'Scope "' . $scope . '" is disabled in plugin settings'];
        }
        return ['allowed' => true, 'status' => 200, 'error' => null];
    }
}

<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

final class ScopeGuard {

    public const FUNCTION_SCOPES = [
        'opc_product_search'  => 'read',
        'opc_product_detail'  => 'read',
        'opc_product_compare' => 'read',
        'opc_stock_check'     => 'read',
        'opc_category_list'   => 'read',
        'opc_order_status'    => 'read',
        'opc_customer_lookup' => 'read',
        'opc_cart_recover'    => 'read',
        'opc_order_preview'   => 'write',
        'opc_order_confirm'   => 'write',
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

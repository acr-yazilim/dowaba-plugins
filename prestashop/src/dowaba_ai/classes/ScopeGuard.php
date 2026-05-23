<?php
class DowabaScopeGuard
{
    public static $FUNCTION_SCOPES = [
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

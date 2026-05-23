<?php
/**
 * Dowaba AI — Scope Guard (OC3, global).
 */
final class DowabaScopeGuard {

    public static $FUNCTION_SCOPES = array(
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
    );

    public static function check($registry, $scope) {
        if (!in_array($scope, array('read', 'write'), true)) {
            return array('allowed' => false, 'status' => 500, 'error' => 'Unknown scope: ' . $scope);
        }
        $enabled = (bool)$registry->get('config')->get('module_dowaba_ai_scope_' . $scope);
        if (!$enabled) {
            return array('allowed' => false, 'status' => 403, 'error' => 'Scope "' . $scope . '" is disabled in plugin admin settings');
        }
        return array('allowed' => true, 'status' => 200, 'error' => null);
    }
}

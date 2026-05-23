<?php
namespace Opencart\System\Library\Extension\DowabaAi;

/**
 * Dowaba AI — Scope Guard.
 *
 * Function execution öncesi: requested scope ('read'|'write') admin tarafında
 * açık mı? Default: read=ON, write=OFF.
 *
 * Write scope kapalıyken AI yine de opc_order_preview/confirm çağırırsa
 * 403 + "Scope 'write' is disabled" cevabı döner — prompt injection koruması.
 *
 * Manifest'te her function'ın metadata'sında "scope": "read"|"write" var (Dowaba
 * şu an okumuyor ama plugin politikasını deklare eder; v0.2'de Dowaba HttpHandler
 * bu hint'i function call öncesi check edebilir).
 */
final class ScopeGuard {

    /** @var array<string,string> function slug → required scope */
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
     * Scope'un açık olup olmadığını döner.
     *
     * @param string $scope 'read' or 'write'
     * @return array{allowed: bool, status: int, error: string|null}
     */
    public static function check($registry, string $scope): array {
        if (!in_array($scope, ['read', 'write'], true)) {
            return ['allowed' => false, 'status' => 500, 'error' => 'Unknown scope: ' . $scope];
        }

        $settingKey = 'module_dowaba_ai_scope_' . $scope;
        $enabled = (bool) $registry->get('config')->get($settingKey);

        if (!$enabled) {
            return [
                'allowed' => false,
                'status'  => 403,
                'error'   => 'Scope "' . $scope . '" is disabled in plugin admin settings',
            ];
        }

        return ['allowed' => true, 'status' => 200, 'error' => null];
    }

    /**
     * Function slug'a göre required scope döner. Bilinmeyen slug → 'read' (en kısıtlı varsayım).
     */
    public static function scopeForFunction(string $slug): string {
        return self::FUNCTION_SCOPES[$slug] ?? 'read';
    }
}

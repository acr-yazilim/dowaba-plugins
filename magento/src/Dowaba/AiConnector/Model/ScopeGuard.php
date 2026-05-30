<?php
/**
 * Dowaba AI Connector — Scope Guard.
 *
 * Before any function executes: is the requested scope ('read'|'write') enabled
 * by the admin? Default read=ON, write=OFF.
 *
 * Write scope being OFF while the AI still calls mgm_order_preview/confirm returns
 * 403 — a prompt-injection safety net (the AI cannot place an order unless the
 * store owner has consciously toggled "write" on).
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Model;

class ScopeGuard
{
    /** @var array<string,string> function slug → required scope */
    public const FUNCTION_SCOPES = [
        'mgm_product_search'  => 'read',
        'mgm_product_detail'  => 'read',
        'mgm_product_compare' => 'read',
        'mgm_stock_check'     => 'read',
        'mgm_category_list'   => 'read',
        'mgm_order_status'    => 'read',
        'mgm_customer_lookup' => 'read',
        'mgm_cart_recover'    => 'read',
        'mgm_order_preview'   => 'write',
        'mgm_order_confirm'   => 'write',
    ];

    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * @return array{allowed: bool, status: int, error: string|null}
     */
    public function check(string $scope): array
    {
        if (!in_array($scope, ['read', 'write'], true)) {
            return ['allowed' => false, 'status' => 500, 'error' => 'Unknown scope: ' . $scope];
        }

        if (!$this->config->isScopeEnabled($scope)) {
            return [
                'allowed' => false,
                'status'  => 403,
                'error'   => 'Scope "' . $scope . '" is disabled in Dowaba AI settings',
            ];
        }

        return ['allowed' => true, 'status' => 200, 'error' => null];
    }

    public function scopeForFunction(string $slug): string
    {
        return self::FUNCTION_SCOPES[$slug] ?? 'read';
    }
}

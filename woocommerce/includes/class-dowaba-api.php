<?php
namespace DowabaAI;

if (!defined('ABSPATH')) exit;

/**
 * REST API endpoints — /wp-json/dowaba/v1/{products,product/{id},compare,...}
 *
 * Her endpoint: Auth::verify → ScopeGuard::check → handler → AuditLogger::write
 */
final class Api {

    private static float $start_time = 0.0;
    private static string $current_slug = '';
    private static string $client_ip = '0.0.0.0';

    public static function register_routes(): void {
        $ns = DOWABA_AI_REST_NAMESPACE;
        $ro = ['methods' => 'GET', 'permission_callback' => '__return_true'];
        $rw = ['methods' => 'POST', 'permission_callback' => '__return_true'];

        register_rest_route($ns, '/products',         array_merge($ro, ['callback' => [self::class, 'products']]));
        register_rest_route($ns, '/product/(?P<id>\d+)', array_merge($ro, ['callback' => [self::class, 'product']]));
        register_rest_route($ns, '/compare',          array_merge($ro, ['callback' => [self::class, 'compare']]));
        register_rest_route($ns, '/stock',            array_merge($ro, ['callback' => [self::class, 'stock']]));
        register_rest_route($ns, '/categories',       array_merge($ro, ['callback' => [self::class, 'categories']]));
        register_rest_route($ns, '/order/(?P<id>\d+)', array_merge($ro, ['callback' => [self::class, 'order_status']]));
        register_rest_route($ns, '/customer/lookup',  array_merge($ro, ['callback' => [self::class, 'customer_lookup']]));
        register_rest_route($ns, '/cart/recover',     array_merge($rw, ['callback' => [self::class, 'cart_recover']]));
        register_rest_route($ns, '/order/preview',    array_merge($rw, ['callback' => [self::class, 'order_preview']]));
        register_rest_route($ns, '/order/confirm',    array_merge($rw, ['callback' => [self::class, 'order_confirm']]));
    }

    // ========================================================== READ

    public static function products(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_product_search';
        if (($g = self::guard($request, 'read'))) return $g;

        $query = trim((string) $request->get_param('q'));
        $limit = max(1, min(50, (int) ($request->get_param('limit') ?: 10)));

        if ($query === '') return self::respond(400, ['error' => 'q parameter required']);

        $products = wc_get_products([
            'limit'   => $limit,
            'status'  => 'publish',
            's'       => $query,
            'orderby' => 'menu_order',
        ]);

        $data = array_map([self::class, 'shape_product'], $products);
        return self::respond(200, ['data' => $data, 'count' => count($data), 'query' => $query]);
    }

    public static function product(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_product_detail';
        if (($g = self::guard($request, 'read'))) return $g;

        $id = (int) $request->get_param('id');
        $product = wc_get_product($id);
        if (!$product) return self::respond(404, ['error' => 'product not found', 'product_id' => $id]);

        $attrs = [];
        foreach ($product->get_attributes() as $name => $attr) {
            if ($attr instanceof \WC_Product_Attribute) {
                $attrs[wc_attribute_label($name)] = $attr->get_options() ? implode(', ', $attr->get_options()) : '';
            }
        }

        return self::respond(200, [
            'data' => array_merge(
                self::shape_product($product),
                [
                    'description'    => wp_strip_all_tags($product->get_description() ?: ''),
                    'short'          => wp_strip_all_tags($product->get_short_description() ?: ''),
                    'sku'            => $product->get_sku(),
                    'weight'         => $product->get_weight(),
                    'attributes'     => $attrs,
                    'gallery_images' => self::shape_gallery($product),
                ]
            ),
        ]);
    }

    public static function compare(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_product_compare';
        if (($g = self::guard($request, 'read'))) return $g;

        $ids_raw = $request->get_param('ids');
        $ids = is_array($ids_raw) ? $ids_raw : explode(',', (string) $ids_raw);
        $ids = array_filter(array_map('intval', $ids), fn($id) => $id > 0);
        $ids = array_values(array_unique($ids));

        if (count($ids) < 2 || count($ids) > 3) {
            return self::respond(400, ['error' => 'ids must contain 2 or 3 unique product IDs', 'received' => $ids]);
        }

        $products = [];
        $all_attrs = [];
        foreach ($ids as $pid) {
            $p = wc_get_product($pid);
            if (!$p) continue;
            $attrs = [];
            foreach ($p->get_attributes() as $name => $attr) {
                if ($attr instanceof \WC_Product_Attribute) {
                    $val = $attr->get_options() ? implode(', ', $attr->get_options()) : '';
                    $label = wc_attribute_label($name);
                    $attrs[$label] = $val;
                    $all_attrs[$label] ??= [];
                    $all_attrs[$label][$pid] = $val;
                }
            }
            $products[] = array_merge(self::shape_product($p), ['attributes' => $attrs]);
        }

        if (count($products) < 2) return self::respond(404, ['error' => 'not enough valid products']);

        $common = [];
        $diff = [];
        foreach ($all_attrs as $name => $values) {
            if (count($values) === count($products) && count(array_unique($values)) === 1) {
                $common[$name] = reset($values);
            } else {
                $diff[$name] = $values;
            }
        }

        return self::respond(200, [
            'data' => [
                'products' => $products,
                'common_attributes' => $common,
                'unique_differences' => $diff,
                'price_range' => [
                    'min' => min(array_column($products, 'price')),
                    'max' => max(array_column($products, 'price')),
                ],
            ],
        ]);
    }

    public static function stock(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_stock_check';
        if (($g = self::guard($request, 'read'))) return $g;

        $pid = (int) $request->get_param('product_id');
        $sku = trim((string) $request->get_param('sku'));

        $product = null;
        if ($pid > 0) $product = wc_get_product($pid);
        elseif ($sku !== '') $product = wc_get_product(wc_get_product_id_by_sku($sku));
        if (!$product) return self::respond(404, ['error' => 'product not found']);

        $stock = (int) ($product->get_stock_quantity() ?: 0);
        return self::respond(200, ['data' => [
            'product_id' => $product->get_id(),
            'name'       => $product->get_name(),
            'sku'        => $product->get_sku(),
            'stock'      => $stock,
            'in_stock'   => $product->is_in_stock(),
            'eta_days'   => $stock > 0 ? 0 : null,
        ]]);
    }

    public static function categories(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_category_list';
        if (($g = self::guard($request, 'read'))) return $g;

        $parent = max(0, (int) $request->get_param('parent_id'));
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'parent'     => $parent,
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) return self::respond(500, ['error' => $terms->get_error_message()]);

        $data = array_map(fn($t) => [
            'category_id' => $t->term_id,
            'name'        => $t->name,
            'parent_id'   => $t->parent,
            'count'       => $t->count,
        ], $terms);

        return self::respond(200, ['data' => $data, 'count' => count($data), 'parent_id' => $parent]);
    }

    public static function order_status(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_order_status';
        if (($g = self::guard($request, 'read'))) return $g;

        $id = (int) $request->get_param('id');
        $email = strtolower(trim((string) $request->get_param('email')));
        if ($id <= 0 || $email === '') return self::respond(400, ['error' => 'id and email required']);

        $order = wc_get_order($id);
        if (!$order || strtolower($order->get_billing_email()) !== $email) {
            return self::respond(404, ['error' => 'order not found']);
        }

        return self::respond(200, ['data' => [
            'order_id'   => $order->get_id(),
            'status'     => $order->get_status(),
            'total'      => (float) $order->get_total(),
            'currency'   => $order->get_currency(),
            'created_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
            'modified_at'=> $order->get_date_modified() ? $order->get_date_modified()->date('c') : null,
            'shipping_city'    => $order->get_shipping_city(),
            'shipping_country' => $order->get_shipping_country(),
        ]]);
    }

    public static function customer_lookup(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_customer_lookup';
        if (($g = self::guard($request, 'read'))) return $g;

        $phone = trim((string) $request->get_param('phone'));
        $email = strtolower(trim((string) $request->get_param('email')));
        if ($phone === '' && $email === '') return self::respond(400, ['error' => 'phone or email required']);

        $user = null;
        if ($email !== '') $user = get_user_by('email', $email);
        // Phone lookup — WC meta search
        if (!$user && $phone !== '') {
            $norm = preg_replace('/\D+/', '', $phone);
            $users = get_users([
                'meta_key'   => 'billing_phone',
                'meta_value' => $norm,
                'number'     => 1,
            ]);
            $user = $users[0] ?? null;
        }

        if (!$user) return self::respond(404, ['error' => 'customer not found']);

        $orders = wc_get_orders([
            'customer_id' => $user->ID,
            'limit'       => 5,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        return self::respond(200, ['data' => [
            'customer_id' => $user->ID,
            'name'        => trim($user->first_name . ' ' . $user->last_name),
            'email'       => $user->user_email,
            'phone'       => get_user_meta($user->ID, 'billing_phone', true),
            'created_at'  => $user->user_registered,
            'recent_orders' => array_map(fn($o) => [
                'order_id' => $o->get_id(),
                'total'    => (float) $o->get_total(),
                'currency' => $o->get_currency(),
                'date'     => $o->get_date_created()->date('c'),
            ], $orders),
        ]]);
    }

    public static function cart_recover(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_cart_recover';
        if (($g = self::guard($request, 'read'))) return $g;

        $body = $request->get_json_params() ?: [];
        $email = strtolower(trim((string) ($body['email'] ?? '')));
        $cid = (int) ($body['customer_id'] ?? 0);
        if ($email === '' && $cid <= 0) return self::respond(400, ['error' => 'email or customer_id required']);

        $token = bin2hex(random_bytes(16));
        $link = wc_get_page_permalink('myaccount') . '?dwb_recover=' . $token;

        return self::respond(200, ['data' => [
            'recover_link' => $link,
            'expires_in'   => 86400,
        ]]);
    }

    // ========================================================== WRITE

    public static function order_preview(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_order_preview';
        if (($g = self::guard($request, 'write'))) return $g;

        $body = $request->get_json_params() ?: $request->get_body_params() ?: [];
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];
        $customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];

        if (empty($items)) return self::respond(400, ['error' => 'items array required']);
        if (count($items) > 50) return self::respond(400, ['error' => 'too many items (max 50)']);

        $phone = trim((string) ($customer['phone'] ?? ''));
        $email = strtolower(trim((string) ($customer['email'] ?? '')));
        if ($phone === '' && $email === '') {
            return self::respond(400, ['error' => 'customer.phone or customer.email required']);
        }

        $resolved = [];
        $subtotal = 0.0;
        $stock_issues = [];

        foreach ($items as $idx => $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if ($pid <= 0) return self::respond(400, ['error' => "items[$idx].product_id required"]);

            $product = wc_get_product($pid);
            if (!$product) return self::respond(404, ['error' => 'product not found', 'product_id' => $pid]);

            $stock = (int) ($product->get_stock_quantity() ?: 0);
            if ($product->managing_stock() && $stock < $qty) {
                $stock_issues[] = [
                    'product_id' => $pid,
                    'name'       => $product->get_name(),
                    'requested_qty'   => $qty,
                    'available_stock' => $stock,
                ];
            }

            $unit_price = (float) $product->get_price();
            $line_total = $unit_price * $qty;
            $subtotal += $line_total;

            $resolved[] = [
                'product_id' => $pid,
                'name'       => $product->get_name(),
                'sku'        => $product->get_sku(),
                'quantity'   => $qty,
                'unit_price' => $unit_price,
                'line_total' => $line_total,
                'stock'      => $stock,
            ];
        }

        if (!empty($stock_issues)) {
            return self::respond(409, [
                'error' => 'insufficient stock',
                'stock_issues' => $stock_issues,
            ]);
        }

        $shipping = $subtotal >= 1000 ? 0.0 : 49.0;
        $total = $subtotal + $shipping;

        $preview_id = OrderPreview::generate_id();
        $payload = [
            'items' => $resolved,
            'customer' => [
                'phone'   => $phone,
                'email'   => $email,
                'name'    => trim((string) ($customer['name'] ?? '')),
                'address' => trim((string) ($customer['address'] ?? '')),
                'city'    => trim((string) ($customer['city'] ?? '')),
            ],
            'subtotal'      => round($subtotal, 2),
            'shipping_cost' => $shipping,
            'total'         => round($total, 2),
            'currency'      => get_woocommerce_currency(),
        ];
        OrderPreview::store($preview_id, $payload);

        return self::respond(200, [
            'preview_id' => $preview_id,
            'expires_at' => date('c', time() + DOWABA_AI_PREVIEW_TTL),
            'expires_in_seconds' => DOWABA_AI_PREVIEW_TTL,
            'summary'    => $payload,
            'note'       => 'AI\'ya: bu özeti müşteriye göster, "Onaylıyor musun?" sor. Onay → wcm_order_confirm.',
        ]);
    }

    public static function order_confirm(\WP_REST_Request $request): \WP_REST_Response {
        self::$current_slug = 'wcm_order_confirm';
        if (($g = self::guard($request, 'write'))) return $g;

        $body = $request->get_json_params() ?: $request->get_body_params() ?: [];
        $preview_id = trim((string) ($body['preview_id'] ?? ''));
        $confirmed = filter_var($body['confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!OrderPreview::is_valid_id($preview_id)) {
            return self::respond(400, ['error' => 'invalid preview_id format']);
        }
        if (!$confirmed) {
            return self::respond(400, ['error' => 'confirmed must be true']);
        }

        $preview = OrderPreview::consume($preview_id);
        if ($preview === null) {
            return self::respond(410, [
                'error' => 'preview_id expired or already consumed',
                'note'  => 'AI\'ya: müşteriden yeniden onay al + yeni wcm_order_preview çağır.',
            ]);
        }

        try {
            $order_id = self::create_order_from_preview($preview);
        } catch (\Throwable $e) {
            return self::respond(500, [
                'error' => 'order creation failed',
                'detail' => $e->getMessage(),
            ]);
        }

        return self::respond(200, ['data' => [
            'order_id'    => $order_id,
            'status'      => 'pending',
            'payment_url' => wc_get_checkout_url() . '?order_id=' . $order_id,
            'total'       => $preview['total'],
            'currency'    => $preview['currency'],
        ], 'note' => 'AI\'ya: müşteriye "Siparişin oluştu #' . $order_id . '" bildir.']);
    }

    // ========================================================== helpers

    private static function create_order_from_preview(array $preview): int {
        $customer = $preview['customer'];
        $name_parts = preg_split('/\s+/', trim($customer['name']), 2);
        $first = $name_parts[0] ?? 'Misafir';
        $last  = $name_parts[1] ?? 'Müşteri';

        $order = wc_create_order();
        foreach ($preview['items'] as $it) {
            $product = wc_get_product((int) $it['product_id']);
            if ($product) {
                $order->add_product($product, (int) $it['quantity']);
            }
        }

        $order->set_address([
            'first_name' => $first,
            'last_name'  => $last,
            'company'    => '',
            'email'      => $customer['email'] ?: 'guest@dowaba.local',
            'phone'      => $customer['phone'],
            'address_1'  => $customer['address'] ?: '—',
            'city'       => $customer['city'] ?: '—',
            'country'    => 'TR',
        ], 'billing');

        $order->set_address([
            'first_name' => $first,
            'last_name'  => $last,
            'address_1'  => $customer['address'] ?: '—',
            'city'       => $customer['city'] ?: '—',
            'country'    => 'TR',
        ], 'shipping');

        $order->set_payment_method('cod');
        $order->set_payment_method_title('Cash on Delivery');
        $order->set_customer_note('Sipariş DoWaba AI üzerinden oluşturuldu (preview_id: ' . ($preview['_preview_id'] ?? '') . ')');
        $order->set_currency($preview['currency'] ?? get_woocommerce_currency());

        // Shipping line — flat rate
        if (($preview['shipping_cost'] ?? 0) > 0) {
            $shipping = new \WC_Order_Item_Shipping();
            $shipping->set_method_title('Flat Rate');
            $shipping->set_method_id('flat_rate');
            $shipping->set_total($preview['shipping_cost']);
            $order->add_item($shipping);
        }

        $order->calculate_totals();
        $order->update_status('pending', 'DoWaba AI ile oluşturuldu — ödeme bekleniyor');
        $order->save();

        return $order->get_id();
    }

    private static function shape_product(\WC_Product $p): array {
        $sale = (float) $p->get_sale_price();
        $regular = (float) $p->get_regular_price();
        $final_price = $sale > 0 && $sale < $regular ? $sale : $regular;

        $image_id = $p->get_image_id();
        $thumb = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : null;
        $cover = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_single') : null;
        if (!$cover && $image_id) $cover = wp_get_attachment_image_url($image_id, 'full');

        return [
            'product_id'     => $p->get_id(),
            'name'           => $p->get_name(),
            'price'          => $final_price,
            'original_price' => $sale > 0 ? $regular : null,
            'currency'       => get_woocommerce_currency(),
            'stock'          => (int) ($p->get_stock_quantity() ?: 0),
            'in_stock'       => $p->is_in_stock(),
            'url'            => $p->get_permalink(),
            'thumb'          => $thumb,
            'cover'          => $cover,
        ];
    }

    /**
     * Ürün galeri görselleri — kapak + WC gallery_image_ids her biri için thumb/medium/full URL.
     * AI panel/widget'in karşılaştırma + slider için kullanır.
     */
    private static function shape_gallery(\WC_Product $p): array {
        $gallery = [];
        $cover_id = $p->get_image_id();
        $ids = array_filter(array_map('intval', (array) $p->get_gallery_image_ids()));
        $all_ids = $cover_id ? array_values(array_unique(array_merge([$cover_id], $ids))) : $ids;

        foreach ($all_ids as $idx => $img_id) {
            $thumb  = wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail');
            $medium = wp_get_attachment_image_url($img_id, 'woocommerce_single');
            $full   = wp_get_attachment_image_url($img_id, 'full');
            if (!$thumb && !$medium && !$full) continue;

            $alt = (string) get_post_meta($img_id, '_wp_attachment_image_alt', true);
            $gallery[] = [
                'image_id' => $img_id,
                'is_cover' => $img_id === $cover_id,
                'position' => $idx,
                'alt'      => $alt,
                'thumb'    => $thumb ?: $medium ?: $full,
                'medium'   => $medium ?: $full,
                'full'     => $full ?: $medium,
            ];
        }
        return $gallery;
    }

    /**
     * Auth + scope check. Fail durumunda WP_REST_Response döner; success'te null.
     */
    private static function guard(\WP_REST_Request $request, string $scope): ?\WP_REST_Response {
        self::$start_time = microtime(true);

        $auth = Auth::verify($request);
        self::$client_ip = $auth['client_ip'];
        if (!$auth['success']) return self::respond($auth['status'], ['error' => $auth['error']]);

        $sg = ScopeGuard::check($scope);
        if (!$sg['allowed']) return self::respond($sg['status'], ['error' => $sg['error']]);

        return null;
    }

    private static function respond(int $status, array $payload): \WP_REST_Response {
        $duration = (int) round((microtime(true) - (self::$start_time ?: microtime(true))) * 1000);

        AuditLogger::write(
            self::$current_slug ?: 'unknown',
            self::$client_ip,
            $status,
            $duration,
            $status >= 400 ? (string) ($payload['error'] ?? null) : null
        );

        $response = new \WP_REST_Response($payload, $status);
        $response->header('X-Dowaba-Duration', (string) $duration);
        return $response;
    }
}

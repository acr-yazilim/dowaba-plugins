<?php
/**
 * Dowaba AI Connector — API function dispatcher.
 *
 * The 10 manifest functions implemented against Magento's service contracts.
 * Each method takes a flat params array (GET query merged with JSON body) and
 * returns ['status' => int, 'body' => array]. HTTP/auth/audit concerns live in
 * Controller\Api\Index — this class is pure store logic.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Model\Api;

use Dowaba\AiConnector\Model\OrderPreview;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Dispatcher
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResourceConnection $resource,
        private readonly QuoteFactory $quoteFactory,
        private readonly QuoteManagement $quoteManagement,
        private readonly OrderPreview $orderPreview,
        private readonly LoggerInterface $logger
    ) {
    }

    // ============================================================ READ functions

    public function products(array $p): array
    {
        $query = trim((string) ($p['q'] ?? ''));
        $limit = max(1, min(50, (int) ($p['limit'] ?? 10)));

        if ($query === '') {
            return ['status' => 400, 'body' => ['error' => 'q parameter required']];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'sku', 'price', 'special_price', 'image', 'status', 'visibility']);
        $collection->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED);
        $collection->addAttributeToFilter([
            ['attribute' => 'name', 'like' => '%' . $query . '%'],
            ['attribute' => 'sku', 'like' => '%' . $query . '%'],
        ]);
        $collection->setPageSize($limit)->setCurPage(1);

        $data = [];
        foreach ($collection as $product) {
            $data[] = $this->shapeProduct($product);
        }

        return ['status' => 200, 'body' => ['data' => $data, 'count' => count($data), 'query' => $query]];
    }

    public function product(array $p): array
    {
        $productId = (int) ($p['id'] ?? 0);
        if ($productId <= 0) {
            return ['status' => 400, 'body' => ['error' => 'id parameter required']];
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Throwable $e) {
            return ['status' => 404, 'body' => ['error' => 'product not found', 'product_id' => $productId]];
        }

        $description = (string) ($product->getCustomAttribute('description')
            ? $product->getCustomAttribute('description')->getValue() : '');
        $cleanDescription = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode($description, ENT_QUOTES, 'UTF-8'))));

        $gallery = $this->shapeGallery($product);

        $body = array_merge($this->shapeProduct($product), [
            'description'   => $cleanDescription,
            'weight'        => $product->getWeight() !== null ? (float) $product->getWeight() : null,
            'attributes'    => $this->shapeAttributes($product),
            'gallery'       => $gallery,        // ek ürün görselleri (OpenCart paritesi)
            'gallery_count' => count($gallery),
        ]);

        return ['status' => 200, 'body' => ['data' => $body]];
    }

    public function compare(array $p): array
    {
        $idsRaw = $p['ids'] ?? '';
        $ids = is_array($idsRaw) ? $idsRaw : explode(',', (string) $idsRaw);
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));

        if (count($ids) < 2 || count($ids) > 3) {
            return ['status' => 400, 'body' => ['error' => 'ids must contain 2 or 3 unique product IDs', 'received' => $ids]];
        }

        $products = [];
        $allAttributes = [];
        foreach ($ids as $pid) {
            try {
                $product = $this->productRepository->getById($pid);
            } catch (\Throwable $e) {
                continue;
            }
            $attrs = $this->shapeAttributes($product);
            $products[] = array_merge($this->shapeProduct($product), ['attributes' => $attrs]);
            foreach ($attrs as $name => $val) {
                $allAttributes[$name][$pid] = $val;
            }
        }

        if (count($products) < 2) {
            return ['status' => 404, 'body' => ['error' => 'not enough valid products found', 'received_ids' => $ids]];
        }

        $common = [];
        $differences = [];
        foreach ($allAttributes as $name => $values) {
            if (count($values) === count($products) && count(array_unique($values)) === 1) {
                $common[$name] = reset($values);
            } else {
                $differences[$name] = $values;
            }
        }

        return ['status' => 200, 'body' => ['data' => [
            'products'           => $products,
            'common_attributes'  => $common,
            'unique_differences' => $differences,
            'price_range' => [
                'min' => min(array_column($products, 'price')),
                'max' => max(array_column($products, 'price')),
            ],
        ]]];
    }

    public function stock(array $p): array
    {
        $productId = (int) ($p['product_id'] ?? 0);
        $sku = trim((string) ($p['sku'] ?? ''));

        if ($productId <= 0 && $sku === '') {
            return ['status' => 400, 'body' => ['error' => 'product_id or sku required']];
        }

        try {
            $product = $productId > 0
                ? $this->productRepository->getById($productId)
                : $this->productRepository->get($sku);
        } catch (\Throwable $e) {
            return ['status' => 404, 'body' => ['error' => 'product not found']];
        }

        $stock = $this->stockQty((int) $product->getId(), (string) $product->getSku());
        return ['status' => 200, 'body' => ['data' => [
            'product_id' => (int) $product->getId(),
            'name'       => (string) $product->getName(),
            'sku'        => (string) $product->getSku(),
            'stock'      => $stock,
            'in_stock'   => $stock > 0,
            'eta_days'   => $stock > 0 ? 0 : null,
        ]]];
    }

    public function categories(array $p): array
    {
        $parentId = max(0, (int) ($p['parent_id'] ?? 0));

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active']);
        $collection->addFieldToFilter('is_active', 1);
        if ($parentId > 0) {
            $collection->addFieldToFilter('parent_id', $parentId);
        } else {
            // root level = children of the store root category (level 2)
            $collection->addFieldToFilter('level', 2);
        }
        $collection->setOrder('position', 'ASC');

        $data = [];
        foreach ($collection as $category) {
            $data[] = [
                'category_id' => (int) $category->getId(),
                'name'        => (string) $category->getName(),
                'parent_id'   => (int) $category->getParentId(),
            ];
        }

        return ['status' => 200, 'body' => ['data' => $data, 'count' => count($data), 'parent_id' => $parentId]];
    }

    public function order(array $p): array
    {
        $orderId = trim((string) ($p['id'] ?? ''));
        $email = strtolower(trim((string) ($p['email'] ?? '')));

        if ($orderId === '' || $email === '') {
            return ['status' => 400, 'body' => ['error' => 'id and email required']];
        }

        $order = $this->findOrder($orderId);
        if ($order === null || strtolower((string) $order->getCustomerEmail()) !== $email) {
            // Generic 404 — IDOR guard: correct id + wrong email is indistinguishable from "not found"
            return ['status' => 404, 'body' => ['error' => 'order not found']];
        }

        $shipping = $order->getShippingAddress();
        return ['status' => 200, 'body' => ['data' => [
            'order_id'         => (string) $order->getIncrementId(),
            'status'           => (string) ($order->getStatusLabel() ?: $order->getStatus()),
            'total'            => (float) $order->getGrandTotal(),
            'currency'         => (string) $order->getOrderCurrencyCode(),
            'created_at'       => (string) $order->getCreatedAt(),
            'modified_at'      => (string) $order->getUpdatedAt(),
            'shipping_city'    => $shipping ? (string) $shipping->getCity() : '',
            'shipping_country' => $shipping ? (string) $shipping->getCountryId() : '',
        ]]];
    }

    public function customerLookup(array $p): array
    {
        $phone = trim((string) ($p['phone'] ?? ''));
        $email = strtolower(trim((string) ($p['email'] ?? '')));

        if ($phone === '' && $email === '') {
            return ['status' => 400, 'body' => ['error' => 'phone or email required']];
        }

        $customer = null;
        if ($email !== '') {
            try {
                $customer = $this->customerRepository->get($email);
            } catch (\Throwable $e) {
                $customer = null;
            }
        }
        if ($customer === null && $phone !== '') {
            $customer = $this->findCustomerByPhone($phone);
        }

        if ($customer === null) {
            return ['status' => 404, 'body' => ['error' => 'customer not found']];
        }

        $recentOrders = $this->recentOrdersForCustomer((int) $customer->getId());

        return ['status' => 200, 'body' => ['data' => [
            'customer_id' => (int) $customer->getId(),
            'name'        => trim($customer->getFirstname() . ' ' . $customer->getLastname()),
            'email'       => (string) $customer->getEmail(),
            'phone'       => $phone,
            'created_at'  => (string) $customer->getCreatedAt(),
            'recent_orders' => $recentOrders,
        ]]];
    }

    public function cartRecover(array $p): array
    {
        $email = strtolower(trim((string) ($p['email'] ?? '')));
        $customerId = (int) ($p['customer_id'] ?? 0);

        if ($email === '' && $customerId <= 0) {
            return ['status' => 400, 'body' => ['error' => 'email or customer_id required']];
        }

        $base = rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
        $token = bin2hex(random_bytes(16));
        $link = $base . '/customer/account/login/?recover=' . $token;

        return ['status' => 200, 'body' => ['data' => [
            'recover_link' => $link,
            'expires_in'   => 86400,
            'note'         => 'Token Faz 2\'de DB\'ye kaydedilip auto-login için bind edilecek',
        ]]];
    }

    // ============================================================ WRITE functions

    public function orderPreview(array $p): array
    {
        $items = is_array($p['items'] ?? null) ? $p['items'] : [];
        $customer = is_array($p['customer'] ?? null) ? $p['customer'] : [];

        if (empty($items)) {
            return ['status' => 400, 'body' => ['error' => 'items array is required and cannot be empty']];
        }
        if (count($items) > 50) {
            return ['status' => 400, 'body' => ['error' => 'too many items (max 50)']];
        }

        $phone = trim((string) ($customer['phone'] ?? ''));
        $email = strtolower(trim((string) ($customer['email'] ?? '')));
        if ($phone === '' && $email === '') {
            return ['status' => 400, 'body' => ['error' => 'customer.phone or customer.email is required']];
        }

        $resolvedItems = [];
        $subtotal = 0.0;
        $stockIssues = [];

        foreach ($items as $idx => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if ($productId <= 0) {
                return ['status' => 400, 'body' => ['error' => 'items[' . $idx . '].product_id required']];
            }

            try {
                $product = $this->productRepository->getById($productId);
            } catch (\Throwable $e) {
                return ['status' => 404, 'body' => ['error' => 'product not found', 'product_id' => $productId]];
            }

            $stock = $this->stockQty($productId, (string) $product->getSku());
            if ($stock < $qty) {
                $stockIssues[] = [
                    'product_id'      => $productId,
                    'name'            => (string) $product->getName(),
                    'requested_qty'   => $qty,
                    'available_stock' => $stock,
                ];
            }

            $unitPrice = $this->effectivePrice($product);
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $resolvedItems[] = [
                'product_id' => $productId,
                'name'       => (string) $product->getName(),
                'sku'        => (string) $product->getSku(),
                'quantity'   => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'stock'      => $stock,
            ];
        }

        if (!empty($stockIssues)) {
            return ['status' => 409, 'body' => [
                'error'        => 'insufficient stock',
                'stock_issues' => $stockIssues,
                'note'         => 'AI\'ya: müşteriye stok yetersizliğini bildir, mevcut adetlerle yeniden preview oluşturmasını teklif et',
            ]];
        }

        $shippingCost = $subtotal >= 1000 ? 0.0 : 49.0;
        $total = $subtotal + $shippingCost;
        $currency = (string) $this->storeManager->getStore()->getCurrentCurrencyCode();

        $previewId = $this->orderPreview->generateId();
        $payload = [
            'items'         => $resolvedItems,
            'customer'      => [
                'phone'   => $phone,
                'email'   => $email,
                'name'    => trim((string) ($customer['name'] ?? '')),
                'address' => trim((string) ($customer['address'] ?? '')),
                'city'    => trim((string) ($customer['city'] ?? '')),
            ],
            'subtotal'      => round($subtotal, 2),
            'shipping_cost' => $shippingCost,
            'total'         => round($total, 2),
            'currency'      => $currency,
        ];
        $this->orderPreview->store($previewId, $payload);

        return ['status' => 200, 'body' => [
            'preview_id'         => $previewId,
            'expires_at'         => date('c', time() + OrderPreview::TTL_SECONDS),
            'expires_in_seconds' => OrderPreview::TTL_SECONDS,
            'summary'            => $payload,
            'note'               => 'AI\'ya: bu özeti müşteriye göster, "Onaylıyor musun?" diye sor. Müşteri "Evet" derse mgm_order_confirm({preview_id, confirmed:true}) çağır.',
        ]];
    }

    public function orderConfirm(array $p): array
    {
        $previewId = trim((string) ($p['preview_id'] ?? ''));
        $confirmed = filter_var($p['confirmed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$this->orderPreview->isValidId($previewId)) {
            return ['status' => 400, 'body' => ['error' => 'invalid preview_id format']];
        }
        if (!$confirmed) {
            return ['status' => 400, 'body' => ['error' => 'confirmed must be true — order_confirm SADECE müşteri açıkça onayladıktan sonra çağrılmalı']];
        }

        $preview = $this->orderPreview->consume($previewId);
        if ($preview === null) {
            return ['status' => 410, 'body' => [
                'error' => 'preview_id expired or already consumed',
                'note'  => 'AI\'ya: müşteriye yeniden onay almak için yeni mgm_order_preview çağır.',
            ]];
        }

        try {
            $order = $this->createOrderFromPreview($preview);
        } catch (\Throwable $e) {
            $this->logger->error('Dowaba order create failed: ' . $e->getMessage());
            return ['status' => 500, 'body' => [
                'error'  => 'order creation failed',
                'detail' => $e->getMessage(),
                'note'   => 'AI\'ya: müşteriye teknik bir hata oluştu, sipariş veritabanına yazılmadı. Manuel deneyecek.',
            ]];
        }

        $base = rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
        return ['status' => 200, 'body' => [
            'data' => [
                'order_id'    => (string) $order->getIncrementId(),
                'status'      => (string) ($order->getStatusLabel() ?: $order->getStatus()),
                'payment_url' => $base . '/checkout/onepage/success/',
                'total'       => $preview['total'],
                'currency'    => $preview['currency'],
            ],
            'note' => 'AI\'ya: müşteriye "Siparişin oluştu #' . $order->getIncrementId() . '" şeklinde bildir.',
        ]];
    }

    /**
     * Build a real Magento order from a consumed preview.
     * v0.1: guest order, Check/Money Order payment, Flat Rate shipping.
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    private function createOrderFromPreview(array $preview): \Magento\Sales\Api\Data\OrderInterface
    {
        $store = $this->storeManager->getStore();
        $customer = $preview['customer'];
        $email = $customer['email'] !== '' ? $customer['email'] : 'guest@dowaba.local';

        $nameParts = preg_split('/\s+/', trim((string) $customer['name']), 2);
        $firstname = $nameParts[0] ?: 'Misafir';
        $lastname  = $nameParts[1] ?? 'Müşteri';

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setCurrency();
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerEmail($email);

        foreach ($preview['items'] as $item) {
            $product = $this->productRepository->getById((int) $item['product_id']);
            $quote->addProduct($product, (int) $item['quantity']);
        }

        $addressData = [
            'firstname'  => $firstname,
            'lastname'   => $lastname,
            'street'     => [$customer['address'] !== '' ? $customer['address'] : '—'],
            'city'       => $customer['city'] !== '' ? $customer['city'] : '—',
            'country_id' => 'TR',
            'region'     => '',
            'postcode'   => '34000',
            'telephone'  => $customer['phone'] !== '' ? $customer['phone'] : '0000000000',
            'email'      => $email,
        ];

        $quote->getBillingAddress()->addData($addressData);
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($addressData);
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');

        $quote->setPaymentMethod('checkmo');
        $quote->setInventoryProcessed(false);
        $quote->save();

        $quote->getPayment()->importData(['method' => 'checkmo']);
        $quote->collectTotals()->save();

        return $this->quoteManagement->submit($quote);
    }

    // ============================================================ helpers

    private function shapeProduct($product): array
    {
        $price = (float) $product->getPrice();
        $special = (float) $product->getSpecialPrice();
        $finalPrice = ($special > 0 && $special < $price) ? $special : $price;

        $store = $this->storeManager->getStore();
        $mediaBase = rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/') . '/catalog/product';
        $imagePath = (string) $product->getData('image');
        $imageUrl = ($imagePath !== '' && $imagePath !== 'no_selection')
            ? $mediaBase . '/' . ltrim($imagePath, '/')
            : null;

        $url = method_exists($product, 'getProductUrl') ? (string) $product->getProductUrl() : '';
        if ($url === '') {
            $url = rtrim($store->getBaseUrl(), '/') . '/catalog/product/view/id/' . (int) $product->getId();
        }

        $stock = $this->stockQty((int) $product->getId(), (string) $product->getSku());

        return [
            'product_id'     => (int) $product->getId(),
            'name'           => html_entity_decode((string) $product->getName(), ENT_QUOTES, 'UTF-8'),
            'sku'            => (string) $product->getSku(),
            'price'          => $finalPrice,
            'original_price' => $special > 0 ? $price : null,
            'currency'       => (string) $store->getCurrentCurrencyCode(),
            'stock'          => $stock,
            'in_stock'       => $stock > 0,
            'url'            => $url,
            'thumb'          => $imageUrl,
            'image'          => $imageUrl,
        ];
    }

    /**
     * All product images (media gallery) as {thumb, image} URL pairs — OpenCart
     * product_detail parity. Best-effort: a gallery read failure never breaks the
     * detail response. getUrl() is preferred; falls back to building the URL from
     * the raw 'file' path against the media base.
     *
     * @return array<int,array{thumb:string,image:string}>
     */
    private function shapeGallery($product): array
    {
        $gallery = [];
        try {
            $store = $this->storeManager->getStore();
            $mediaBase = rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/') . '/catalog/product';
            $images = $product->getMediaGalleryImages();
            if ($images) {
                foreach ($images as $img) {
                    $url = (string) $img->getUrl();
                    if ($url === '') {
                        $file = (string) $img->getData('file');
                        if ($file === '' || $file === 'no_selection') {
                            continue;
                        }
                        $url = $mediaBase . '/' . ltrim($file, '/');
                    }
                    $gallery[] = ['thumb' => $url, 'image' => $url];
                }
            }
        } catch (\Throwable $e) {
            // best-effort — detail still returns without gallery
        }
        return $gallery;
    }

    /**
     * Flatten visible-on-front product attributes into name => value text.
     */
    private function shapeAttributes($product): array
    {
        $flat = [];
        try {
            $attributes = $product->getAttributes();
            foreach ($attributes as $attribute) {
                if (!$attribute->getIsVisibleOnFront()) {
                    continue;
                }
                $code = $attribute->getAttributeCode();
                if (in_array($code, ['name', 'price', 'special_price', 'description', 'short_description', 'image'], true)) {
                    continue;
                }
                $value = $attribute->getFrontend()->getValue($product);
                if ($value === null || $value === '' || is_array($value)) {
                    continue;
                }
                $label = (string) ($attribute->getStoreLabel() ?: $attribute->getDefaultFrontendLabel() ?: $code);
                $flat[$label] = is_scalar($value) ? (string) $value : '';
            }
        } catch (\Throwable $e) {
            // best-effort — detail response still returns without attributes
        }
        return $flat;
    }

    private function effectivePrice($product): float
    {
        $price = (float) $product->getPrice();
        $special = (float) $product->getSpecialPrice();
        return ($special > 0 && $special < $price) ? $special : $price;
    }

    private function stockQty(int $productId, string $sku): int
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($productId);
            return (int) $stockItem->getQty();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Resolve an order by increment_id, falling back to numeric entity_id.
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    private function findOrder(string $orderId)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('increment_id', $orderId);
        $collection->setPageSize(1);
        $order = $collection->getFirstItem();
        if ($order->getId()) {
            return $order;
        }

        if (ctype_digit($orderId)) {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('entity_id', (int) $orderId);
            $collection->setPageSize(1);
            $order = $collection->getFirstItem();
            if ($order->getId()) {
                return $order;
            }
        }

        return null;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function findCustomerByPhone(string $phone)
    {
        try {
            $norm = preg_replace('/\D+/', '', $phone);
            if ($norm === '') {
                return null;
            }
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('customer_address_entity');
            $select = $connection->select()
                ->from($table, 'parent_id')
                ->where("REPLACE(REPLACE(REPLACE(telephone, ' ', ''), '-', ''), '+', '') = ?", $norm)
                ->limit(1);
            $customerId = $connection->fetchOne($select);
            if (!$customerId) {
                return null;
            }
            return $this->customerRepository->getById((int) $customerId);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function recentOrdersForCustomer(int $customerId): array
    {
        try {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('customer_id', $customerId);
            $collection->setOrder('entity_id', 'DESC');
            $collection->setPageSize(5);

            $orders = [];
            foreach ($collection as $order) {
                $orders[] = [
                    'order_id' => (string) $order->getIncrementId(),
                    'total'    => (float) $order->getGrandTotal(),
                    'currency' => (string) $order->getOrderCurrencyCode(),
                    'date'     => (string) $order->getCreatedAt(),
                ];
            }
            return $orders;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

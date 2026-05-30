<?php
/**
 * Dowaba AI Connector — public manifest endpoint.
 *
 * GET /dowaba_ai/manifest → bundle JSON the Dowaba panel imports. No auth (public
 * info: function definitions + URLs + param schemas). Real auth happens per call
 * via Authorization: Bearer ... in the API controller.
 *
 * base_url is resolved from the request host (tunnel/proxy-aware), NOT the static
 * install URL — see LESSONS_LEARNED bug #3.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Controller\Manifest;

use Dowaba\AiConnector\Model\Config;
use Dowaba\AiConnector\Model\Manifest;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly RawFactory $rawFactory,
        private readonly HttpRequest $request,
        private readonly Manifest $manifest,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function execute()
    {
        $baseUrl = $this->resolveBaseUrl();

        try {
            $store = $this->storeManager->getStore();
            $storeName = (string) ($store->getFrontendName() ?: $store->getName());
        } catch (\Throwable $e) {
            $storeName = 'Magento Store';
        }

        $manifest = $this->manifest->build($baseUrl, $storeName);
        $json = json_encode(
            $manifest,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'application/json; charset=utf-8');
        $result->setHeader('Access-Control-Allow-Origin', '*'); // manifest is public read
        $result->setHeader('Cache-Control', 'public, max-age=300');
        $result->setContents($json);
        return $result;
    }

    /**
     * Tunnel/proxy-aware base URL resolver.
     * Priority: admin override → X-Forwarded-* → HTTP_HOST → store base URL.
     */
    private function resolveBaseUrl(): string
    {
        $override = $this->config->getManifestBaseUrlOverride();
        if ($override !== '') {
            return rtrim($override, '/');
        }

        $forwardedHost = (string) ($this->request->getServer('HTTP_X_FORWARDED_HOST') ?? '');
        $forwardedProto = (string) ($this->request->getServer('HTTP_X_FORWARDED_PROTO') ?? '');
        if ($forwardedHost !== '') {
            $scheme = $forwardedProto !== '' ? $forwardedProto : 'https';
            return $scheme . '://' . $forwardedHost;
        }

        $host = (string) ($this->request->getServer('HTTP_HOST') ?? '');
        if ($host !== '') {
            $https = (string) ($this->request->getServer('HTTPS') ?? '');
            $port = (string) ($this->request->getServer('SERVER_PORT') ?? '');
            $isHttps = ($https !== '' && $https !== 'off') || $port === '443';
            return ($isHttps ? 'https' : 'http') . '://' . $host;
        }

        try {
            return rtrim($this->storeManager->getStore()->getBaseUrl(), '/');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

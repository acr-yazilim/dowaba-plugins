<?php
/**
 * Dowaba AI Connector — connection test (AJAX).
 *
 * Fetches the store's own manifest URL and validates the JSON, so the admin can
 * confirm the endpoint is reachable before pasting it into the Dowaba panel.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;

class TestConnection extends Action
{
    public const ADMIN_RESOURCE = 'Dowaba_AiConnector::settings';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Curl $curl,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $manifestUrl = $this->buildManifestUrl();

        try {
            $this->curl->setOption(CURLOPT_TIMEOUT, 10);
            $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 5);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, false);
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->get($manifestUrl);
            $status = $this->curl->getStatus();
            $body = (string) $this->curl->getBody();
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'error' => 'Connection error: ' . $e->getMessage(), 'url' => $manifestUrl]);
        }

        if ($status !== 200) {
            return $result->setData(['success' => false, 'error' => 'HTTP ' . $status, 'url' => $manifestUrl]);
        }

        $manifest = json_decode($body, true);
        if (!is_array($manifest) || !isset($manifest['schema_version'])) {
            return $result->setData(['success' => false, 'error' => 'Invalid manifest JSON', 'url' => $manifestUrl]);
        }

        return $result->setData([
            'success'        => true,
            'url'            => $manifestUrl,
            'schema_version' => $manifest['schema_version'],
            'function_count' => count($manifest['functions'] ?? []),
        ]);
    }

    private function buildManifestUrl(): string
    {
        try {
            $base = $this->storeManager->getDefaultStoreView()->getBaseUrl();
        } catch (\Throwable $e) {
            $base = $this->storeManager->getStore()->getBaseUrl();
        }
        return rtrim($base, '/') . '/dowaba_ai/manifest';
    }
}

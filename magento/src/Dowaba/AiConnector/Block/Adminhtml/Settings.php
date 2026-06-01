<?php
/**
 * Dowaba AI Connector — admin settings block.
 *
 * Feeds the setup-wizard template (settings.phtml) with config values, the
 * (secret-keyed) admin action URLs and the public manifest URL.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Block\Adminhtml;

use Dowaba\AiConnector\Model\Config;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\StoreManagerInterface;

class Settings extends Template
{
    /** Dowaba production egress IPs (server2 + server3) — suggested whitelist. */
    public const DOWABA_PROD_IPS = '178.105.68.170, 49.13.120.112';

    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly FormKey $formKeyProvider,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isModuleEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function isScopeRead(): bool
    {
        return $this->config->isScopeEnabled('read');
    }

    public function isScopeWrite(): bool
    {
        return $this->config->isScopeEnabled('write');
    }

    public function getIpWhitelistValue(): string
    {
        return $this->config->getIpWhitelist();
    }

    public function getManifestBaseUrlOverride(): string
    {
        return $this->config->getManifestBaseUrlOverride();
    }

    public function getApiKeyPrefixValue(): string
    {
        return $this->config->getApiKeyPrefix();
    }

    public function getApiKeyLastUsedValue(): string
    {
        return $this->config->getApiKeyLastUsed();
    }

    public function getAuditRetentionValue(): int
    {
        return $this->config->getAuditRetentionDays();
    }

    public function hasApiKey(): bool
    {
        return $this->config->getApiKeyPrefix() !== '';
    }

    public function getManifestUrl(): string
    {
        try {
            $base = $this->storeManager->getDefaultStoreView()->getBaseUrl();
        } catch (\Throwable $e) {
            $base = $this->storeManager->getStore()->getBaseUrl();
        }
        return rtrim($base, '/') . '/dowaba_ai/manifest';
    }

    public function getDowabaFormKey(): string
    {
        return $this->formKeyProvider->getFormKey();
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('dowaba_ai/settings/save');
    }

    public function getRegenerateUrl(): string
    {
        return $this->getUrl('dowaba_ai/settings/regeneratekey');
    }

    public function getTestUrl(): string
    {
        return $this->getUrl('dowaba_ai/settings/testconnection');
    }

    public function getAuditUrl(): string
    {
        return $this->getUrl('dowaba_ai/settings/auditlog');
    }

    public function getSuggestedIps(): string
    {
        return self::DOWABA_PROD_IPS;
    }
}

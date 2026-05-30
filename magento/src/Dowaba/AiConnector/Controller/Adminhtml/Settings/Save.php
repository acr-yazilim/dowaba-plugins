<?php
/**
 * Dowaba AI Connector — persist settings (status, scopes, IP whitelist, retention).
 *
 * A single config-cache flush after the batch save (see Model\Config) so the
 * frontend API immediately sees the new values.
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Controller\Adminhtml\Settings;

use Dowaba\AiConnector\Model\Config;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Dowaba_AiConnector::settings';

    public function __construct(
        Context $context,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();

        if (!$request->isPost()) {
            return $resultRedirect->setPath('*/*/index');
        }

        $ipWhitelist = trim((string) $request->getParam('ip_whitelist', ''));
        if ($ipWhitelist !== '') {
            foreach (array_map('trim', explode(',', $ipWhitelist)) as $ip) {
                if ($ip !== '' && !filter_var($ip, FILTER_VALIDATE_IP)) {
                    $this->messageManager->addErrorMessage(__('Invalid IP in whitelist: %1', $ip));
                    return $resultRedirect->setPath('*/*/index');
                }
            }
        }

        $retention = max(1, (int) $request->getParam('audit_retention_days', 30));

        $this->config->saveValue(Config::XML_STATUS, $request->getParam('status') ? '1' : '0');
        $this->config->saveValue(Config::XML_SCOPE_READ, $request->getParam('scope_read') ? '1' : '0');
        $this->config->saveValue(Config::XML_SCOPE_WRITE, $request->getParam('scope_write') ? '1' : '0');
        $this->config->saveValue(Config::XML_IP_WHITELIST, $ipWhitelist);
        $this->config->saveValue(Config::XML_MANIFEST_BASE_URL, trim((string) $request->getParam('manifest_base_url', '')));
        $this->config->saveValue(Config::XML_AUDIT_RETENTION, (string) $retention);
        $this->config->flushConfigCache();

        $this->messageManager->addSuccessMessage(__('Dowaba AI settings saved.'));
        return $resultRedirect->setPath('*/*/index');
    }
}

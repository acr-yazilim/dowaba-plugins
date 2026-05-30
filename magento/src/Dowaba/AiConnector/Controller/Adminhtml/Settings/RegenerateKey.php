<?php
/**
 * Dowaba AI Connector — generate a new API key (AJAX).
 *
 * The plain key is returned ONCE in this response. Afterwards only the sha256 hash
 * + 12-char prefix are stored. A config-cache flush makes the new hash live
 * immediately for the frontend API.
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
use Magento\Framework\Controller\Result\JsonFactory;

class RegenerateKey extends Action
{
    public const ADMIN_RESOURCE = 'Dowaba_AiConnector::settings';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $plainKey = 'mgm_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $plainKey);
        $prefix = substr($plainKey, 0, 12);

        $this->config->saveValue(Config::XML_API_KEY_HASH, $hash);
        $this->config->saveValue(Config::XML_API_KEY_PREFIX, $prefix);
        $this->config->saveValue(Config::XML_API_KEY_LAST_USED, '');
        $this->config->flushConfigCache();

        return $result->setData([
            'success'   => true,
            'plain_key' => $plainKey, // shown only here, never retrievable again
            'prefix'    => $prefix,
        ]);
    }
}

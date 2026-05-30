<?php
/**
 * Dowaba AI Connector — admin settings page (5-step setup wizard).
 *
 * @author    Aydın Acar <destek@dowaba.com>
 * @copyright 2026 Dowaba (https://dowaba.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Dowaba\AiConnector\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Dowaba_AiConnector::settings';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Dowaba_AiConnector::settings');
        $resultPage->getConfig()->getTitle()->prepend(__('Dowaba AI — Setup & Settings'));
        return $resultPage;
    }
}

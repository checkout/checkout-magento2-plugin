<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 8
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Controller\Adminhtml\Logs;

use CheckoutCom\Magento2\Model\Service\LogFileResolverService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class View
 */
class View extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @var string
     */
    public const ADMIN_RESOURCE = LogFileResolverService::ACL_RESOURCE;

    protected PageFactory $resultPageFactory;
    private LogFileResolverService $logFileResolver;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LogFileResolverService $logFileResolver
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->logFileResolver = $logFileResolver;
    }

    /**
     * Load the page defined in view/adminhtml/layout/cko_logs_view.xml
     *
     * @return Page|Redirect
     */
    public function execute()
    {
        if ($this->logFileResolver->getFilePath($this->getRequest()->getParam('file')) === null) {
            $this->messageManager->addErrorMessage(__('The requested log file is not available.'));

            return $this->resultRedirectFactory->create()->setPath(
                'adminhtml/system_config/edit',
                ['section' => 'payment']
            );
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Log File Reader'));

        return $resultPage;
    }
}

<?php
/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */
namespace CheckoutCom\Magento2\Block\Flow;

use CheckoutCom\Magento2\ViewModel\Flow\LoadMainScript;
use Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context as TemplateContext;

class FlowScript extends Template
{
    protected LoadMainScript $mainScriptViewModel;

    public function __construct(
        TemplateContext $context,
        LoadMainScript $mainScriptViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->mainScriptViewModel = $mainScriptViewModel;
    }

    public function useFlow(): bool
    {
        return $this->mainScriptViewModel->useFlow();
    }
}

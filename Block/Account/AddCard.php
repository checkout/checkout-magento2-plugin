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

declare(strict_types=1);

namespace CheckoutCom\Magento2\Block\Account;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class AddCard
 */
class AddCard extends Template
{
    /**
     * Config field
     *
     * @var Config $config
     */
    private $config;

    /**
     * AddCard constructor
     *
     * @param Context $context
     * @param Config  $config
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Get config
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}

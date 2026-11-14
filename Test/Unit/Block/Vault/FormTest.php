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

namespace CheckoutCom\Magento2\Test\Unit\Block\Vault;

use CheckoutCom\Magento2\Block\Vault\Form;
use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function testGetConfigReturnsInjectedConfig(): void
    {
        $config = $this->createStub(Config::class);

        $block = (new ObjectManager($this))->getObject(
            Form::class,
            [
                'cardHandler' => $this->createStub(CardHandlerService::class),
                'vaultHandler' => $this->createStub(VaultHandlerService::class),
                'config' => $config,
            ]
        );

        $this->assertSame($config, $block->getConfig());
    }
}

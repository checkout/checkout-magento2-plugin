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

namespace CheckoutCom\Magento2\Model\Ui;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Gateway\Config\Loader;
use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\QuoteHandlerService;
use CheckoutCom\Magento2\Model\Service\ShopperHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;
    /**
     * $shopperHandler field
     *
     * @var ShopperHandlerService $shopperHandler
     */
    private $shopperHandler;
    /**
     * $quoteHandler field
     *
     * @var QuoteHandlerService $quoteHandler
     */
    private $quoteHandler;
    /**
     * $cardHandler field
     *
     * @var CardHandlerService $cardHandler
     */
    private $cardHandler;
    /**
     * $methodHandler field
     *
     * @var MethodHandlerService $methodHandler
     */
    private $methodHandler;
    /**
     * $vaultHandler
     *
     * @var VaultHandlerService $vaultHandler
     */
    private $vaultHandler;

    /**
     * ConfigProvider constructor
     *
     * @param Config                $config
     * @param ShopperHandlerService $shopperHandler
     * @param QuoteHandlerService   $quoteHandler
     * @param VaultHandlerService   $vaultHandler
     * @param CardHandlerService    $cardHandler
     * @param MethodHandlerService  $methodHandler
     */
    public function __construct(
        Config $config,
        ShopperHandlerService $shopperHandler,
        QuoteHandlerService $quoteHandler,
        VaultHandlerService $vaultHandler,
        CardHandlerService $cardHandler,
        MethodHandlerService $methodHandler
    ) {
        $this->config         = $config;
        $this->shopperHandler = $shopperHandler;
        $this->quoteHandler   = $quoteHandler;
        $this->vaultHandler   = $vaultHandler;
        $this->cardHandler    = $cardHandler;
        $this->methodHandler  = $methodHandler;
    }

    /**
     * Send the configuration to the frontend
     *
     * @return mixed[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        return [
            Loader::KEY_PAYMENT => [
                Loader::KEY_MODULE_ID => $this->getConfigArray(),
            ],
        ];
    }

    /**
     * Returns a merged array of config values
     *
     * @return mixed[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfigArray(): array
    {
        return array_merge($this->config->getModuleConfig(), $this->config->getMethodsConfig(), [
                'checkoutcom_data' => [
                    'quote'            => $this->quoteHandler->getQuoteData(),
                    'store'            => [
                        'name'     => $this->config->getStoreName(),
                        'language' => $this->config->getStoreLanguage(),
                        'code'     => $this->config->getStoreCode(),
                        'country'  => $this->config->getStoreCountry()
                    ],
                    'user'             => [
                        'has_cards'         => $this->vaultHandler->userHasCards(),
                        'language_fallback' => $this->shopperHandler->getLanguageFallback(),
                        'previous_method'   => $this->methodHandler->getPreviousMethod(),
                        'previous_source'   => $this->methodHandler->getPreviousSource()
                    ],
                    'cards'            => $this->cardHandler->getCardIcons(),
                    'images_path'      => $this->config->getImagesPath(),
                    'css_path'         => $this->config->getCssPath(),
                    'use_minified_css' => $this->config->getCoreValue('dev/css/minify_files')
                ],
            ]);
    }
}

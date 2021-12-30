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

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Exception;
use Magento\Framework\File\Csv;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\View\Asset\Repository;

/**
 * Class CardHandlerService
 *
 * @category  Magento2
 * @package   Checkout.com
 */
class CardHandlerService
{
    /**
     * $cardMapper field
     *
     * @var array $cardMapper
     */
    public static $cardMapper = [
        'VI'  => 'Visa',
        'MC'  => 'Mastercard',
        'AE'  => 'American Express',
        'DN'  => 'Diners Club International',
        'DI'  => 'Discover',
        'JCB' => 'JCB',
    ];
    /**
     * $assetRepository field
     *
     * @var Repository $assetRepository
     */
    public $assetRepository;
    /**
     * $directoryReader field
     *
     * @var Reader $directoryReader
     */
    public $directoryReader;
    /**
     * $csvParser field
     *
     * @var Csv $csvParser
     */
    public $csvParser;
    /**
     * $config field
     *
     * @var Config $config
     */
    public $config;

    /**
     * CardHandlerService constructor
     *
     * @param Repository $assetRepository
     * @param Reader     $directoryReader
     * @param Csv        $csvParser
     * @param Config     $config
     */
    public function __construct(
        Repository $assetRepository,
        Reader $directoryReader,
        Csv $csvParser,
        Config $config
    ) {
        $this->assetRepository = $assetRepository;
        $this->directoryReader = $directoryReader;
        $this->csvParser       = $csvParser;
        $this->config          = $config;
    }

    /**
     * Get a card code from name
     *
     * @param $scheme
     *
     * @return false|int|string
     */
    public function getCardCode($scheme)
    {
        if ($scheme === 'Amex') {
            $scheme = 'American Express';
        }

        return array_search(
            $scheme,
            self::$cardMapper
        );
    }

    /**
     * Get a card scheme from code
     *
     * @param $code
     *
     * @return mixed|string|void
     */
    public function getCardScheme($code)
    {
        if (isset(self::$cardMapper[$code])) {
            return self::$cardMapper[$code];
        }
    }

    /**
     * Get a card icon
     *
     * @param $code
     *
     * @return string
     */
    public function getCardIcon($code)
    {
        return $this->assetRepository->getUrl(
            'CheckoutCom_Magento2::images/cc/' . strtolower($code) . '.svg'
        );
    }

    /**
     * Get all card icons
     *
     * @return array
     */
    public function getCardIcons()
    {
        // Prepare the output array
        $output = [];

        // Get the selected cards
        $selectedCards = explode(
            ',',
            $this->config->getValue(
                'card_icons',
                'checkoutcom_card_payment'
            )
        );

        // Build the cards list
        foreach (self::$cardMapper as $code => $value) {
            if (in_array($code, $selectedCards)) {
                $output[] = [
                    'code' => $code,
                    'name' => __($value),
                    'url'  => $this->assetRepository->getUrl(
                        'CheckoutCom_Magento2::images/cc/' . strtolower($code) . '.svg'
                    ),
                ];
            }
        }

        return $output;
    }

    /**
     * Check if a card is active
     *
     * @param $card
     *
     * @return bool
     */
    public function isCardActive($card)
    {
        return $card->getIsActive() && $card->getIsVisible() && $card->getPaymentMethodCode() === 'checkoutcom_vault';
    }

    /**
     * Checks the MADA BIN
     *
     * @param $bin
     *
     * @return bool
     * @throws Exception
     */
    public function isMadaBin($bin)
    {
        // Set the root path
        $csvPath = $this->directoryReader->getModuleDir(
                '',
                'CheckoutCom_Magento2'
            ) . '/' . $this->config->getMadaBinFile();

        // Get the data
        $csvData = $this->csvParser->getData($csvPath);

        // Remove the first row of csv columns
        unset($csvData[0]);

        // Build the MADA BIN array
        $binArray = [];
        foreach ($csvData as $row) {
            $binArray[] = $row[1];
        }

        return in_array($bin, $binArray);
    }
}

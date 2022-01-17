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

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use Exception;
use Magento\Framework\File\Csv;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\View\Asset\Repository;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Class CardHandlerService
 */
class CardHandlerService
{
    /**
     * CARD_MAPPER field
     *
     * @var array CARD_MAPPER
     */
    const CARD_MAPPER = [
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
    private $assetRepository;
    /**
     * $directoryReader field
     *
     * @var Reader $directoryReader
     */
    private $directoryReader;
    /**
     * $csvParser field
     *
     * @var Csv $csvParser
     */
    private $csvParser;
    /**
     * $config field
     *
     * @var Config $config
     */
    private $config;

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
     * @param string $scheme
     *
     * @return false|int|string
     */
    public function getCardCode(string $scheme)
    {
        if ($scheme === 'Amex') {
            $scheme = 'American Express';
        }

        return array_search(
            $scheme,
            self::CARD_MAPPER
        );
    }

    /**
     * Get a card scheme from code
     *
     * @param string $code
     *
     * @return mixed|string|void
     */
    public function getCardScheme(string $code)
    {
        if (isset(self::CARD_MAPPER[$code])) {
            return self::CARD_MAPPER[$code];
        }
    }

    /**
     * Get a card icon
     *
     * @param string $code
     *
     * @return string
     */
    public function getCardIcon(string $code): string
    {
        return $this->assetRepository->getUrl(
            'CheckoutCom_Magento2::images/cc/' . strtolower($code) . '.svg'
        );
    }

    /**
     * Get all card icons
     *
     * @return mixed[]
     */
    public function getCardIcons(): array
    {
        // Prepare the output array
        $output = [];

        /** @var string|null $cardIcons */
        $cardIcons = $this->config->getValue(
            'card_icons',
            'checkoutcom_card_payment'
        );

        if (!$cardIcons) {
            return $output;
        }

        // Get the selected cards
        $selectedCards = explode(',', $cardIcons);

        // Build the cards list
        foreach (self::CARD_MAPPER as $code => $value) {
            if (!in_array($code, $selectedCards)) {
                continue;
            }
            $output[] = [
                'code' => $code,
                'name' => __($value),
                'url'  => $this->assetRepository->getUrl(
                    'CheckoutCom_Magento2::images/cc/' . strtolower($code) . '.svg'
                ),
            ];
        }

        return $output;
    }

    /**
     * Check if a card is active
     *
     * @param PaymentTokenInterface $card
     *
     * @return bool
     */
    public function isCardActive(PaymentTokenInterface $card): bool
    {
        return $card->getIsActive() && $card->getIsVisible() && $card->getPaymentMethodCode() === 'checkoutcom_vault';
    }

    /**
     * Checks the MADA BIN
     *
     * @param string|int $bin
     *
     * @return bool
     * @throws Exception
     */
    public function isMadaBin($bin): bool
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

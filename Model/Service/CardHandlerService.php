<?php

namespace CheckoutCom\Magento2\Model\Service;

class CardHandlerService
{
    /**
     * @var array
     */
    protected static $cardMapper = [
        'VI' => 'Visa',
        'MC' => 'Mastercard',
        'AE' => 'American Express',
        'DN' => 'Diners Club International',
        'DI' => 'Discover',
        'JCB' => 'JCB'
    ];

    /**
     * @var Repository
     */
    protected $assetRepository;

    /**
     * @var Reader
     */
    protected $directoryReader;

    /**
     * @var Csv
     */
    protected $csvParser;

    /**
     * @var Config
     */
    protected $config;

	/**
     * CardHandlerService constructor.
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\Module\Dir\Reader $directoryReader,
        \Magento\Framework\File\Csv $csvParser,
        \CheckoutCom\Magento2\Gateway\Config\Config $config
    )
    {
        $this->assetRepository = $assetRepository;
        $this->directoryReader = $directoryReader;
        $this->csvParser = $csvParser;
        $this->config = $config;
    }

    /**
     * Get a card code from name.
     *
     * @return string
     */
    public function getCardCode($scheme) {
        return array_search(
            $scheme,
            static::$cardMapper
        );
    }

    /**
     * Get a card scheme from code.
     *
     * @return string
     */
    public function getCardScheme($code) {
        return static::$cardMapper[$code];
    }

    /**
     * Get a card icon.
     *
     * @return string
     */
    public function getCardIcon($code) {
        return $this->assetRepository
            ->getUrl(
                'CheckoutCom_Magento2::images/cc/' . strtolower($code) . '.svg'
            );
    }

    /**
     * Check if a card is active.
     *
     * @return bool
     */
    public function isCardActive($card) {
        return $card->getIsActive() 
        && $card->getIsVisible()
        && $card->getPaymentMethodCode() == 'checkoutcom_vault';
    }

    /**
     * Checks the MADA BIN
     *
     * @return bool
     */
    public function isMadaBin($bin) {
        // Set the root path
        $csvPath = $this->directoryReader->getModuleDir(
            '',
            'CheckoutCom_Magento2'
        )  . '/' . $this->config->getMadaBinFile();

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
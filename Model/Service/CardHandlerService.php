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
     * CardHandlerService constructor.
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepository
    )
    {
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get a card code from name.
     *
     * @return string
     */
    public function getCardCode($scheme) {
        return array_search(
            $scheme,
            self::$cardMapper
        );
    }

    /**
     * Get a card scheme from code.
     *
     * @return string
     */
    public function getCardScheme($code) {
        return self::$cardMapper[$code];
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
}

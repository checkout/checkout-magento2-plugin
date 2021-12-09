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

namespace CheckoutCom\Magento2\Block\Adminhtml\Alerts;

use CheckoutCom\Magento2\Model\Service\VersionHandlerService;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Phrase;

/**
 * Class VersionNotification
 */
class VersionNotification implements MessageInterface
{
    public $versionHandler;
    public $versions;
    public $current;
    public $latest;

    /**
     * VersionNotification constructor
     *
     * @param VersionHandlerService $versionHandler
     */
    public function __construct(
        VersionHandlerService $versionHandler
    ) {
        $this->versionHandler = $versionHandler;
        $this->versions       = $this->versionHandler->getVersions();
    }

    /**
     * Description getText function
     *
     * @return Phrase
     */
    public function getText()
    {
        $message = __(
            'Please keep your website safe! Your checkout plugin (v' . $this->current . ') is not the latest version (v' . $this->latest . ').
         Update now to get the latest features and security updates.
         See https://github.com/checkout/checkout-magento2-plugin for detailed instructions.'
        );

        return $message;
    }

    /**
     * Description getIdentity function
     *
     * @return false|string
     */
    public function getIdentity()
    {
        return hash('sha256', 'Checkout.com' . time());
    }

    /**
     * Description isDisplayed function
     *
     * @return bool|void
     */
    public function isDisplayed()
    {
        if (isset($this->versions[0]['tag_name'])) {
            $this->current = $this->versionHandler->getModuleVersion();
            $this->latest  = $this->versionHandler->getLatestVersion($this->versions);
            if ($this->versionHandler->needsUpdate($this->current, $this->latest)) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Description getSeverity function
     *
     * @return int
     */
    public function getSeverity()
    {
        $releaseType = $this->versionHandler->getVersionType($this->current, $this->latest);

        switch ($releaseType) {
            case 'revision':
                return self::SEVERITY_MINOR;

            case 'minor':
                return self::SEVERITY_MAJOR;

            case 'major':
                return self::SEVERITY_CRITICAL;

            default:
                return self::SEVERITY_NOTICE;
        }
    }
}

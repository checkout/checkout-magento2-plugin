<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as BaseHandler;
use Monolog\Level as MonologLevel;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class InfoHandler extends BaseHandler
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/checkoutcom_magento2_gateway.log';
    /**
     * @var int
     */
    protected $loggerType = MonologLevel::Info;
}

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
 * @copyright 2010-present Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

declare(strict_types=1);

namespace CheckoutCom\Magento2\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class CreateVerificationDate implements DataPatchInterface
{
    private const VERIFICATION_DATE_PATH = 'settings/checkoutcom_configuration/verification_date';

    private $writer;

    public function __construct(
        WriterInterface $writer
    ) {
        $this->writer = $writer;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        $this->writer->save(self::VERIFICATION_DATE_PATH, date('Y-m-d H:i:s'));

        return $this;
    }
}

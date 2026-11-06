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

namespace CheckoutCom\Magento2\Test\Unit\Model\Service;

use CheckoutCom\Magento2\Model\Service\ApmRequestValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApmRequestValidatorTest extends TestCase
{
    private const SOURCE_ID = 'src_wmlfc3zyhqzehihu7giusaaawu';

    public static function acceptedUrls(): array
    {
        return [
            ['https://api.checkout.com/sepa/mandates/' . self::SOURCE_ID, false, null],
            ['https://api.sandbox.checkout.com/sepa/mandates/' . self::SOURCE_ID, true, null],
            ['https://abc123de.api.checkout.com/sepa/mandates/' . self::SOURCE_ID, false, 'ABC123DE'],
            ['https://abc123de.api.sandbox.checkout.com/sepa/mandates/' . self::SOURCE_ID, true, 'abc123de'],
            ['https://api.checkout.com/sepa/mandates/' . self::SOURCE_ID, false, 'abc123de'],
        ];
    }

    public static function refusedUrls(): array
    {
        $path = '/sepa/mandates/' . self::SOURCE_ID;

        return [
            ['', false, null],
            ['https://example.com' . $path, false, null],
            ['https://api.checkout.com.example.com' . $path, false, null],
            ['https://apicheckout.com' . $path, false, null],
            ['https://evil.api.checkout.com' . $path, false, 'abc123de'],
            ['https://abc123de.api.checkout.com' . $path, false, null],
            ['https://a.abc123de.api.checkout.com' . $path, false, 'abc123de'],
            ['https://api.sandbox.checkout.com' . $path, false, null],
            ['https://api.checkout.com' . $path, true, null],
            ['http://api.checkout.com' . $path, false, null],
            ['//api.checkout.com' . $path, false, null],
            ['ftp://api.checkout.com' . $path, false, null],
            ['https://user@api.checkout.com' . $path, false, null],
            ['https://api.checkout.com@example.com' . $path, false, null],
            ['https://api.checkout.com:8443' . $path, false, null],
            ['https://api.checkout.com' . $path . '?x=1', false, null],
            ['https://api.checkout.com' . $path . '#x', false, null],
            ['https://API.CHECKOUT.COM' . $path, false, null],
            ['https://api.checkout.com.' . $path, false, null],
            ['https://api.checkout.com\\@example.com' . $path, false, null],
            ['https://api.checkout.com/sepa/../mandates/' . self::SOURCE_ID, false, null],
            ['https://api.checkout.com/sepa/mandates/%73rc_wmlfc3zyhqzehihu7giusaaawu', false, null],
            ['https://api.checkout.com/payments/' . self::SOURCE_ID, false, null],
            ['https://api.checkout.com' . $path . '/', false, null],
            ['https://api.checkout.com/sepa/mandates/pay_wmlfc3zyhqzehihu7giusaaawu', false, null],
            ['https://api.checkout.com/sepa/mandates/src_abc', false, null],
            ["https://api.checkout.com" . $path . "\r\nHost: example.com", false, null],
            ["https://api.checkout.com" . $path . "\0", false, null],
            [' https://api.checkout.com' . $path, false, null],
            ['https://api.checkоut.com' . $path, false, null],
            ['https://a.b.api.checkout.com' . $path, false, 'a.b'],
        ];
    }

    #[DataProvider('acceptedUrls')]
    public function testMandateSourceIdForExpectedUrls(string $url, bool $sandbox, ?string $prefix): void
    {
        $this->assertSame(self::SOURCE_ID, (new ApmRequestValidator())->getMandateSourceId($url, $sandbox, $prefix));
    }

    #[DataProvider('refusedUrls')]
    public function testMandateSourceIdIsNullForOtherValues(string $url, bool $sandbox, ?string $prefix): void
    {
        $this->assertNull((new ApmRequestValidator())->getMandateSourceId($url, $sandbox, $prefix));
    }

    public function testEnabledMethods(): void
    {
        $validator = new ApmRequestValidator();

        $this->assertTrue($validator->isEnabledMethod('sepa', 'ideal,sepa'));
        $this->assertTrue($validator->isEnabledMethod('ideal', ' ideal , sepa '));
        $this->assertFalse($validator->isEnabledMethod('sepa', 'ideal,eps'));
        $this->assertFalse($validator->isEnabledMethod('sepa', ''));
        $this->assertFalse($validator->isEnabledMethod('sepa', null));
        $this->assertFalse($validator->isEnabledMethod('', 'ideal,'));
        $this->assertFalse($validator->isEnabledMethod('SEPA', 'sepa'));
        $this->assertFalse($validator->isEnabledMethod('sep', 'sepa'));
    }
}

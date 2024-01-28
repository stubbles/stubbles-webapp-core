<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertFalse;
use function bovigo\assert\assertThat;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function stubbles\reflect\annotationsOf;
/**
 * Test for stubbles\webapp\request\UserAgent.
 *
 * @since  1.2.0
 */
#[Group('request')]
class UserAgentTest extends TestCase
{
    private UserAgent $userAgent;

    protected function setUp(): void
    {
        $this->userAgent = new UserAgent('name', true);
    }

    #[Test]
    public function xmlAnnotationPresentClass(): void
    {
        assertTrue(annotationsOf($this->userAgent)->contain('XmlTag'));
    }

    public static function getXmlRelatedMethodAnnotations(): Generator
    {
        yield ['name', 'XmlAttribute'];
        yield ['isBot', 'XmlAttribute'];
        yield ['acceptsCookies', 'XmlAttribute'];
        yield ['__toString', 'XmlIgnore'];
    }

    #[Test]
    #[DataProvider('getXmlRelatedMethodAnnotations')]
    public function xmlAnnotationsPresentOnMethods(string $method, string $annotation): void
    {
        assertTrue(
            annotationsOf($this->userAgent, $method)->contain($annotation)
        );
    }

    #[Test]
    public function instanceReturnsGivenName(): void
    {
        assertThat($this->userAgent->name(), equals('name'));
    }

    #[Test]
    public function castToStringReturnsName(): void
    {
        assertThat((string) $this->userAgent, equals('name'));
    }

    public static function botsRecognizedByDefault(): Generator
    {
        yield ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
        yield ['Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'];
        yield ['Microsoft msnbot 3.2'];
        yield ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'];
        yield ['Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534+ (KHTML, like Gecko) BingPreview/1.0b'];
        yield ['Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)'];
        yield ['Pingdom.com_bot_version_1.4_(http://www.pingdom.com/)'];
        yield ['Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)'];
    }

    /**
     * @since  4.1.0
     */
    #[Test]
    #[DataProvider('botsRecognizedByDefault')]
    public function recognizesSomeBotsByDefault(string $userAgentValue): void
    {
        $userAgent = new UserAgent($userAgentValue, true);
        assertTrue($userAgent->isBot());
    }

    /**
     * @since  9.0.0
     */
    #[Test]
    public function userAgentIsNoBotWhenUserAgentStringIsNull(): void
    {
        $userAgent = new UserAgent(null, true);
        assertFalse($userAgent->isBot());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function instanceReturnsGivenCookieAcceptanceSetting(): void
    {
        assertTrue($this->userAgent->acceptsCookies());
    }
}

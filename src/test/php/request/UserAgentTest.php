<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function stubbles\reflect\annotationsOf;
/**
 * Test for stubbles\webapp\request\UserAgent.
 *
 * @since  1.2.0
 * @group  request
 */
class UserAgentTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\request\UserAgent
     */
    private $userAgent;

    protected function setUp(): void
    {
        $this->userAgent = new UserAgent('name', true);
    }

    /**
     * @test
     */
    public function xmlAnnotationPresentClass()
    {
        assertTrue(annotationsOf($this->userAgent)->contain('XmlTag'));
    }

    public function getXmlRelatedMethodAnnotations(): array
    {
        return [['name', 'XmlAttribute'],
                ['isBot', 'XmlAttribute'],
                ['acceptsCookies', 'XmlAttribute'],
                ['__toString', 'XmlIgnore']
        ];
    }

    /**
     * @test
     * @dataProvider  getXmlRelatedMethodAnnotations
     */
    public function xmlAnnotationsPresentOnMethods(string $method, string $annotation)
    {
        assertTrue(
                annotationsOf($this->userAgent, $method)->contain($annotation)
        );
    }

    /**
     * @test
     */
    public function instanceReturnsGivenName()
    {
        assertThat($this->userAgent->name(), equals('name'));
    }

    /**
     * @test
     */
    public function castToStringReturnsName()
    {
        assertThat((string) $this->userAgent, equals('name'));
    }


    public function botsRecognizedByDefault(): array
    {
        return [
            ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            ['Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            ['Microsoft msnbot 3.2'],
            ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            ['Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534+ (KHTML, like Gecko) BingPreview/1.0b'],
            ['Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)'],
            ['Pingdom.com_bot_version_1.4_(http://www.pingdom.com/)'],
            ['Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)']
        ];
    }

    /**
     * @since  4.1.0
     * @test
     * @dataProvider  botsRecognizedByDefault
     */
    public function recognizesSomeBotsByDefault(string $userAgentValue)
    {
        $userAgent = new UserAgent($userAgentValue, true);
        assertTrue($userAgent->isBot());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function instanceReturnsGivenCookieAcceptanceSetting()
    {
        assertTrue($this->userAgent->acceptsCookies());
    }
}

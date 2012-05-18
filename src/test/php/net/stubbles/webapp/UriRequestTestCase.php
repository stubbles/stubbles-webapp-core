<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
/**
 * Tests for net\stubbles\webapp\UriRequest.
 *
 * @since  1.7.0
 * @group  core
 */
class UriRequestTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  UriRequest
     */
    private $uriRequest;
    /**
     * mocked http uri
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpUri;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockHttpUri = $this->getMock('net\stubbles\peer\http\HttpUri');
        $this->uriRequest  = new UriRequest($this->mockHttpUri);
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function canCreateInstanceFromString()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\UriRequest',
                                UriRequest::fromString('http://example.net/')
        );
    }

    /**
     * mocks uri path
     *
     * @param  string  $path
     */
    private function mockUriPath($path)
    {
        $this->mockHttpUri->expects($this->any())
                          ->method('getPath')
                          ->will($this->returnValue($path));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsUriPath()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertEquals('/xml/Home', $this->uriRequest->getPath());
    }

    /**
     * @test
     */
    public function alwaysSatisfiesNullCondition()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertTrue($this->uriRequest->satisfies(null));
    }

    /**
     * @test
     */
    public function alwaysSatisfiesEmptyCondition()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertTrue($this->uriRequest->satisfies(''));
    }

    /**
     * @test
     */
    public function returnsTrueForSatisfiedCondition()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertTrue($this->uriRequest->satisfies('^/xml/'));
    }

    /**
     * @test
     */
    public function returnsFalseForNonSatisfiedCondition()
    {
        $this->mockUriPath('/rss/articles');
        $this->assertFalse($this->uriRequest->satisfies('^/xml/'));
    }

    /**
     * @test
     */
    public function getProcessorUriReturnsSlashOnlyWhenNoProcessorUriConditionSet()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertEquals('/',
                            $this->uriRequest->getProcessorUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsEverythingExceptSlashOnlyWhenNoProcessorUriConditionSet()
    {
        $this->mockUriPath('/Home');
        $this->assertEquals('Home',
                            $this->uriRequest->getRemainingUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsEverythingIncludingDotsExceptSlashOnlyWhenNoProcessorUriConditionSet()
    {
        $this->mockUriPath('/Home.html');
        $this->assertEquals('Home.html',
                            $this->uriRequest->getRemainingUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsEverythingExceptParametersOnlyWhenNoProcessorUriConditionSet()
    {
        $this->mockUriPath('/Home?foo=bar');
        $this->assertEquals('Home',
                            $this->uriRequest->getRemainingUri()
        );
    }

    /**
      * @test
      */
    public function getRemainingUriReturnsEmptyStringOnlyWhenNoProcessorUriConditionSetAndNoRemainingPartLeft()
    {
        $this->mockUriPath('/');
        $this->assertEquals('',
                            $this->uriRequest->getRemainingUri()
        );
    }

    /**
      * @test
      */
    public function getRemainingUriReturnsEmptyStringOnlyWhenNoProcessorUriConditionSetAndOnlyParametersLeft()
    {
        $this->mockUriPath('/?foo=bar');
        $this->assertEquals('',
                            $this->uriRequest->getRemainingUri()
        );
    }

    /**
      * @test
      */
    public function getRemainingUriReturnsFallbackOnlyWhenNoProcessorUriConditionSetAndNoRemainingPartLeft()
    {
        $this->mockUriPath('/');
        $this->assertEquals('index',
                            $this->uriRequest->getRemainingUri('index')
        );
    }

    /**
      * @test
      */
    public function getRemainingUriReturnsFallbackOnlyWhenNoProcessorUriConditionSetAndOnlyParametersLeft()
    {
        $this->mockUriPath('/?foo=bar');
        $this->assertEquals('index',
                            $this->uriRequest->getRemainingUri('index')
        );
    }

    /**
     * @test
     */
    public function getProcessorUriReturnsEmptyStringOnNonMatch()
    {
        $this->mockUriPath('/other/Home');
        $this->assertEquals('',
                            $this->uriRequest->setProcessorUriCondition('^/xml/')
                                             ->getProcessorUri()
        );
    }

    /**
     * @test
     */
    public function getProcessorUriReturnsProcessorPartWhenProcessorUriConditionSet()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertEquals('/xml/',
                            $this->uriRequest->setProcessorUriCondition('^/xml/')
                                             ->getProcessorUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsNonProcessorPartWhenProcessorUriConditionSet()
    {
        $this->mockUriPath('/xml/Home');
        $this->assertEquals('Home',
                            $this->uriRequest->setProcessorUriCondition('^/xml/')
                                             ->getRemainingUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsNonProcessorPartWithoutParametersWhenProcessorUriConditionSet()
    {
        $this->mockUriPath('/xml/Home?foo=bar');
        $this->assertEquals('Home',
                            $this->uriRequest->setProcessorUriCondition('^/xml/')
                                             ->getRemainingUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsEmptyStringWhenProcessorUriConditionSetButUriDoesNotContainMore()
    {
        $this->mockUriPath('/xml/');
        $this->assertEquals('',
                            $this->uriRequest->setProcessorUriCondition('^/xml/')
                                             ->getRemainingUri()
        );
    }

    /**
     * @test
     */
    public function getRemainingUriReturnsEmptyStringWhenProcessorUriConditionSetButUriDoesContainOnlyParameters()
    {
        $this->mockUriPath('/xml/?foo=bar');
        $this->assertEquals('',
                            $this->uriRequest->setProcessorUriCondition('^/xml/')
                                             ->getRemainingUri()
        );
    }

    /**
      * @test
      */
    public function getRemainingUriReturnsFallbackWhenProcessorUriConditionSetButUriDoesNotContainMore()
    {
        $this->mockUriPath('/xml/');
        $this->assertEquals('index',
                             $this->uriRequest->setProcessorUriCondition('^/xml/')
                                              ->getRemainingUri('index')
         );
    }

    /**
      * @test
      */
    public function getRemainingUriReturnsFallbackWhenProcessorUriConditionSetButUriDoesContainOnlyParameters()
    {
        $this->mockUriPath('/xml/?foo=bar');
        $this->assertEquals('index',
                             $this->uriRequest->setProcessorUriCondition('^/xml/')
                                              ->getRemainingUri('index')
         );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function isSslWhenRequestUriHasHttps()
    {
        $this->mockHttpUri->expects($this->once())
                          ->method('isHttps')
                          ->will($this->returnValue(true));
        $this->assertTrue($this->uriRequest->isSsl());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpReturnsTransformedUri()
    {
        $mockHttpUri = $this->getMock('net\stubbles\peer\http\HttpUri');
        $this->mockHttpUri->expects($this->once())
                          ->method('toHttp')
                          ->will($this->returnValue($mockHttpUri));
        $this->assertSame($mockHttpUri, $this->uriRequest->toHttp());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpsReturnsTransformedUri()
    {
        $mockHttpUri = $this->getMock('net\stubbles\peer\http\HttpUri');
        $this->mockHttpUri->expects($this->once())
                          ->method('toHttps')
                          ->will($this->returnValue($mockHttpUri));
        $this->assertSame($mockHttpUri, $this->uriRequest->toHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsStringRepresentationOfUri()
    {
        $this->mockHttpUri->expects($this->once())
                          ->method('__toString')
                          ->will($this->returnValue('http://example.net/foo/bar'));
        $this->assertEquals('http://example.net/foo/bar', (string) $this->uriRequest);
    }
}
?>
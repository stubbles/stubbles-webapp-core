<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
/**
 * Tests for stubbles\webapp\UriRequest.
 *
 * @since  1.7.0
 * @group  core
 */
class UriRequestTest extends \PHPUnit_Framework_TestCase
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
        $this->mockHttpUri = $this->getMock('stubbles\peer\http\HttpUri');
        $this->uriRequest  = new UriRequest($this->mockHttpUri, 'GET');
    }

    /**
     * @since  2.0.0
     * @test
     * @deprecated  since 4.0.0, will be removed with 5.0.0
     */
    public function canCreateInstanceFromString()
    {
        $this->assertInstanceOf('stubbles\webapp\UriRequest',
                                UriRequest::fromString('http://example.net/', 'GET')
        );
    }

    /**
     * @since  4.0.0
     * @return  array
     */
    public function emptyRequestMethods()
    {
        return [[null], ['']];
    }

    /**
     * @since  4.0.0
     * @param  string  $empty
     * @test
     * @dataProvider  emptyRequestMethods
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     */
    public function createInstanceWithEmptyRequestMethodThrowsIllegalArgumentException($empty)
    {
        new UriRequest($this->mockHttpUri, $empty);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromOtherInstanceReturnsInstance()
    {
        $this->assertSame($this->uriRequest, UriRequest::castFrom($this->uriRequest, null));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceReturnsInstance()
    {
        $this->assertEquals($this->uriRequest, UriRequest::castFrom($this->mockHttpUri, 'GET'));
    }

    /**
     * @test
     * @dataProvider  emptyRequestMethods
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceWithoutRequestMethodThrowsIllegalArgumentException($empty)
    {
        UriRequest::castFrom($this->mockHttpUri, $empty);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriStringReturnsInstance()
    {

        $this->assertEquals(new UriRequest('http://example.net/', 'GET'), UriRequest::castFrom('http://example.net/', 'GET'));
    }

    /**
     * @test
     * @dataProvider  emptyRequestMethods
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     * @since  4.0.0
     */
    public function castFromHttpUriStringWithoutRequestMethodThrowsIllegalArgumentException($empty)
    {
        UriRequest::castFrom('http://example.net/', $empty);
    }

    /**
     * mocks uri path
     *
     * @param  string  $path
     */
    private function mockUriPath($path)
    {
        $this->mockHttpUri->expects($this->any())
                          ->method('path')
                          ->will($this->returnValue($path));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsNullMethod()
    {
        $this->assertTrue($this->uriRequest->methodEquals(null));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsEmptyMethod()
    {
        $this->assertTrue($this->uriRequest->methodEquals(''));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodEqualsGivenMethod()
    {
        $this->assertTrue($this->uriRequest->methodEquals('GET'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodDoesNotEqualsGivenMethod()
    {
        $this->assertFalse($this->uriRequest->methodEquals('POST'));
    }

    /**
     * data provider for satisfying path pattern tests
     *
     * @return  array
     */
    public function provideSatisfiedPathPattern()
    {
        return [
            ['/hello/mikey', '/hello/{name}$'],
            ['/hello/mikey/foo', '/hello/{name}'],
            ['/hello', '/hello'],
            ['/hello/world303', '/hello/[a-z0-9]+'],
            ['/', '/'],
            ['/hello', ''],
            ['/hello', null]
        ];
    }

    /**
     * @test
     * @dataProvider  provideSatisfiedPathPattern
     */
    public function returnsTrueForSatisfiedPathPattern($mockPath, $path)
    {
        $this->mockUriPath($mockPath);
        $this->assertTrue($this->uriRequest->satisfiesPath($path));
    }

    /**
     * data provider for non satisfying path pattern tests
     *
     * @return  array
     */
    public function provideNonSatisfiedPathPattern()
    {
        return [['/rss/articles', '/hello/{name}'],
                ['/hello/mikey', '/hello$'],
                ['/hello/', '/hello/{name}$'],
                ['/hello/mikey', '/$'],
                ['/hello/mikey', '$']
        ];
    }

    /**
     * @test
     * @dataProvider  provideNonSatisfiedPathPattern
     */
    public function returnsFalseForNonSatisfiedCondition($mockPath, $pathPattern)
    {
        $this->mockUriPath($mockPath);
        $this->assertFalse($this->uriRequest->satisfiesPath($pathPattern));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function isHttpsWhenRequestUriHasHttps()
    {
        $this->mockHttpUri->expects($this->once())
                          ->method('isHttps')
                          ->will($this->returnValue(true));
        $this->assertTrue($this->uriRequest->isHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpReturnsTransformedUri()
    {
        $mockHttpUri = $this->getMock('stubbles\peer\http\HttpUri');
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
        $mockHttpUri = $this->getMock('stubbles\peer\http\HttpUri');
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

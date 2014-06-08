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
        $this->mockHttpUri = $this->getMock('stubbles\peer\http\HttpUri');
        $this->uriRequest  = new UriRequest($this->mockHttpUri, 'GET');
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function canCreateInstanceFromString()
    {
        $this->assertInstanceOf('stubbles\webapp\UriRequest',
                                UriRequest::fromString('http://example.net/', 'GET')
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
     * data provider for satisfying path pattern tests
     *
     * @return  array
     */
    public function providePathArguments()
    {
        return [['/hello/mikey', '/hello/{name}', ['name' => 'mikey']],
                ['/hello/303/mikey', '/hello/{id}/{name}', ['id' => '303', 'name' => 'mikey']]
        ];
    }

    /**
     * @test
     * @dataProvider  providePathArguments
     */
    public function returnsPathArguments($mockPath, $path, array $arguments)
    {
        $this->mockUriPath($mockPath);
        $this->assertEquals($arguments, $this->uriRequest->getPathArguments($path));
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
     * data provider for remaining uri tests
     *
     * @return  array
     */
    public function provideRemainingUris()
    {
        return [['/hello/mikey', '/hello/{name}', ''],
                ['/hello/303/mikey', '/hello/{id}/{name}', ''],
                ['/hello/303/mikey/foo', '/hello/{id}/{name}', '/foo'],
                ['/hello', '/hello', ''],
                ['/hello/world;name', '/hello/[a-z0-9]+;?', 'name'],
                ['/hello/world', '/hello/?', 'world'],
                ['/', '/', '']
        ];
    }

    /**
     * @test
     * @dataProvider  provideRemainingUris
     */
    public function returnsRemainingUri($mockPath, $pathPattern, $expected)
    {
        $this->mockUriPath($mockPath);
        $this->assertEquals($expected,
                            $this->uriRequest->getRemainingPath($pathPattern)
        );
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

<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
/**
 * Tests for stubbles\webapp\CalledUri.
 *
 * @since  1.7.0
 * @group  core
 */
class CalledUriTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\CalledUri
     */
    private $calledUri;
    /**
     * mocked http uri
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $httpUri;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->httpUri   = $this->getMock('stubbles\peer\http\HttpUri');
        $this->calledUri = new CalledUri($this->httpUri, 'GET');
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
     * @expectedException  InvalidArgumentException
     */
    public function createInstanceWithEmptyRequestMethodThrowsIllegalArgumentException($empty)
    {
        new CalledUri($this->httpUri, $empty);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromOtherInstanceReturnsInstance()
    {
        assertSame(
                $this->calledUri,
                CalledUri::castFrom($this->calledUri, null)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceReturnsInstance()
    {
        assertEquals(
                $this->calledUri,
                CalledUri::castFrom($this->httpUri, 'GET')
        );
    }

    /**
     * @test
     * @dataProvider  emptyRequestMethods
     * @expectedException  InvalidArgumentException
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceWithoutRequestMethodThrowsIllegalArgumentException($empty)
    {
        CalledUri::castFrom($this->httpUri, $empty);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriStringReturnsInstance()
    {

        assertEquals(
                new CalledUri('http://example.net/', 'GET'),
                CalledUri::castFrom('http://example.net/', 'GET')
        );
    }

    /**
     * @test
     * @dataProvider  emptyRequestMethods
     * @expectedException  InvalidArgumentException
     * @since  4.0.0
     */
    public function castFromHttpUriStringWithoutRequestMethodThrowsIllegalArgumentException($empty)
    {
        CalledUri::castFrom('http://example.net/', $empty);
    }

    /**
     * mocks uri path
     *
     * @param  string  $path
     */
    private function mockUriPath($path)
    {
        $this->httpUri->method('path')->will($this->returnValue($path));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsNullMethod()
    {
        assertTrue($this->calledUri->methodEquals(null));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsEmptyMethod()
    {
        assertTrue($this->calledUri->methodEquals(''));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodEqualsGivenMethod()
    {
        assertTrue($this->calledUri->methodEquals('GET'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodDoesNotEqualsGivenMethod()
    {
        assertFalse($this->calledUri->methodEquals('POST'));
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
    public function returnsTrueForSatisfiedPathPattern($path, $pathPattern)
    {
        $this->mockUriPath($path);
        assertTrue($this->calledUri->satisfiesPath($pathPattern));
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
    public function returnsFalseForNonSatisfiedCondition($path, $pathPattern)
    {
        $this->mockUriPath($path);
        assertFalse($this->calledUri->satisfiesPath($pathPattern));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function isHttpsWhenRequestUriHasHttps()
    {
        $this->httpUri->method('isHttps')->will(returnValue(true));
        assertTrue($this->calledUri->isHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpReturnsTransformedUri()
    {
        $httpUri = $this->getMock('stubbles\peer\http\HttpUri');
        $this->httpUri->method('toHttp')->will(returnValue($httpUri));
        assertSame($httpUri, $this->calledUri->toHttp());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpsReturnsTransformedUri()
    {
        $httpUri = $this->getMock('stubbles\peer\http\HttpUri');
        $this->httpUri->method('toHttps')->will(returnValue($httpUri));
        assertSame($httpUri, $this->calledUri->toHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsStringRepresentationOfUri()
    {
        $this->httpUri->method('__toString')
                ->will(returnValue('http://example.net/foo/bar'));
        assertEquals(
                'http://example.net/foo/bar',
                (string) $this->calledUri
        );
    }
}

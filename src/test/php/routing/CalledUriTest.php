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
use bovigo\callmap\NewInstance;
use stubbles\peer\http\HttpUri;

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\webapp\CalledUri.
 *
 * @since  1.7.0
 * @group  routing
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
     * @type  \bovigo\callmap\Proxy
     */
    private $httpUri;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->httpUri   = NewInstance::stub(HttpUri::class);
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
     */
    public function createInstanceWithEmptyRequestMethodThrowsIllegalArgumentException($empty)
    {
        expect(function() use ($empty) { new CalledUri($this->httpUri, $empty); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromOtherInstanceReturnsInstance()
    {
        assert(
                CalledUri::castFrom($this->calledUri, null),
                isSameAs($this->calledUri)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceReturnsInstance()
    {
        assert(
                CalledUri::castFrom($this->httpUri, 'GET'),
                equals($this->calledUri)
        );
    }

    /**
     * @test
     * @dataProvider  emptyRequestMethods
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceWithoutRequestMethodThrowsIllegalArgumentException($empty)
    {
        expect(function() use ($empty) { CalledUri::castFrom($this->httpUri, $empty); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriStringReturnsInstance()
    {

        assert(
                CalledUri::castFrom('http://example.net/', 'GET'),
                equals(new CalledUri('http://example.net/', 'GET'))
        );
    }

    /**
     * @test
     * @dataProvider  emptyRequestMethods
     * @since  4.0.0
     */
    public function castFromHttpUriStringWithoutRequestMethodThrowsIllegalArgumentException($empty)
    {
        expect(function() use ($empty) { CalledUri::castFrom('http://example.net/', $empty); })
                ->throws(\InvalidArgumentException::class);
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
        $this->httpUri->mapCalls(['path' => $path]);
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
        $this->httpUri->mapCalls(['path' => $path]);
        assertFalse($this->calledUri->satisfiesPath($pathPattern));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function isHttpsWhenRequestUriHasHttps()
    {
        $this->httpUri->mapCalls(['isHttps' => true]);
        assertTrue($this->calledUri->isHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpReturnsTransformedUri()
    {
        $httpUri = NewInstance::stub(HttpUri::class);
        $this->httpUri->mapCalls(['toHttp' => $httpUri]);
        assert($this->calledUri->toHttp(), isSameAs($httpUri));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpsReturnsTransformedUri()
    {
        $httpUri = NewInstance::stub(HttpUri::class);
        $this->httpUri->mapCalls(['toHttps' => $httpUri]);
        assert($this->calledUri->toHttps(), isSameAs($httpUri));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsStringRepresentationOfUri()
    {
        $this->httpUri->mapCalls(['__toString' => 'http://example.net/foo/bar']);
        assert((string) $this->calledUri, equals('http://example.net/foo/bar'));
    }
}

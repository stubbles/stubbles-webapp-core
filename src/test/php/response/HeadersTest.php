<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\peer\http\HttpUri;

use function bovigo\assert\{
    assert,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals,
    predicate\isInstanceOf
};
/**
 * Tests for stubbles\webapp\response\Headers.
 *
 * @group  response
 * @sicne  4.0.0
 */
class HeadersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  Headers
     */
    private $headers;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->headers = new Headers();
    }

    /**
     * @test
     */
    public function doesNotContainHeaderWhenNotAdded()
    {
        assertFalse($this->headers->contain('X-Foo'));
    }

    /**
     * @test
     */
    public function doesNotContainHeaderWhenNotAddedWithArrayAccess()
    {
        assertFalse(isset($this->headers['X-Foo']));
    }

    /**
     * @test
     */
    public function containsHeaderWhenAdded()
    {
        assertTrue(
                $this->headers->add('X-Foo', 'bar')->contain('X-Foo')
        );
    }

    /**
     * @test
     */
    public function containsHeaderWhenAddedWithArrayAccess()
    {
        $this->headers->add('X-Foo', 'bar');
        assertTrue(isset($this->headers['X-Foo']));
    }

    /**
     * @test
     */
    public function containsHeaderWhenAddedWithArrayAccess2()
    {
        $this->headers['X-Foo'] = 'bar';
        assertTrue(isset($this->headers['X-Foo']));
    }

    /**
     * @test
     */
    public function locationHeaderAcceptsUriAsString()
    {
        $this->headers->location('http://example.com/');
        assertTrue(isset($this->headers['Location']));
        assert($this->headers['Location'], equals('http://example.com/'));
    }

    /**
     * @test
     */
    public function locationHeaderAcceptsUriAsHttpUri()
    {
        $this->headers->location(HttpUri::fromString('http://example.com/'));
        assertTrue(isset($this->headers['Location']));
        assert($this->headers['Location'], equals('http://example.com/'));
    }

    /**
     * @test
     */
    public function allowAddsListOfAllowedMethods()
    {
        $this->headers->allow(['POST', 'PUT']);
        assertTrue(isset($this->headers['Allow']));
        assert($this->headers['Allow'], equals('POST, PUT'));
    }

    /**
     * @test
     */
    public function acceptableDoesNotAddListOfSupportedMimeTypesWhenListEmpty()
    {
        assertFalse(
                $this->headers->acceptable([])->contain('X-Acceptable')
        );
    }

    /**
     * @test
     */
    public function acceptableAddsListOfSupportedMimeTypesWhenListNotEmpty()
    {
        $this->headers->acceptable(['text/csv', 'application/json']);
        assertTrue(isset($this->headers['X-Acceptable']));
        assert($this->headers['X-Acceptable'], equals('text/csv, application/json'));
    }

    /**
     * @test
     */
    public function forceDownloadAddesContentDispositionHeaderWithGivenFilename()
    {
        $this->headers->forceDownload('example.csv');
        assertTrue(isset($this->headers['Content-Disposition']));
        assert(
                $this->headers['Content-Disposition'],
                equals('attachment; filename="example.csv"')
        );
    }

    /**
     * @test
     */
    public function isIterable()
    {
        $this->headers->add('X-Foo', 'bar');
        foreach ($this->headers as $name => $value) {
            assert($name, equals('X-Foo'));
            assert($value, equals('bar'));
        }
    }

    /**
     * @test
     */
    public function unsetViaArrayAccessThrowsBadMethodCallException()
    {
        $this->headers->add('X-Foo', 'bar');
        expect(function() { unset($this->headers['X-Foo']); })
                ->throws(\BadMethodCallException::class);
    }

    /**
     * @test
     * @group  issue_71
     * @since  5.1.0
     */
    public function cacheControlAddsCacheControlHeaderWithDefaultValue()
    {
        $this->headers->cacheControl();
        assert($this->headers[CacheControl::HEADER_NAME], equals('private'));
    }

    /**
     * @test
     * @group  issue_71
     * @since  5.1.0
     */
    public function cacheControlReturnsCacheControlInstance()
    {
        assert($this->headers->cacheControl(), isInstanceOf(CacheControl::class));
    }

    /**
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdAddsRequestIdHeader()
    {
        $this->headers->requestId('example-request-id-foo');
        assertTrue(isset($this->headers['X-Request-ID']));
        assert($this->headers['X-Request-ID'], equals('example-request-id-foo'));
    }

    /**
     * @test
     * @since  5.1.0
     */
    public function ageAddsAgeHeader()
    {
        $this->headers->age(12);
        assertTrue(isset($this->headers['Age']));
        assert($this->headers['Age'], equals(12));
    }
}

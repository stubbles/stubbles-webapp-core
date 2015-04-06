<?php
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
        assertEquals('http://example.com/', $this->headers['Location']);
    }

    /**
     * @test
     */
    public function locationHeaderAcceptsUriAsHttpUri()
    {
        $this->headers->location(HttpUri::fromString('http://example.com/'));
        assertTrue(isset($this->headers['Location']));
        assertEquals('http://example.com/', $this->headers['Location']);
    }

    /**
     * @test
     */
    public function allowAddsListOfAllowedMethods()
    {
        $this->headers->allow(['POST', 'PUT']);
        assertTrue(isset($this->headers['Allow']));
        assertEquals('POST, PUT', $this->headers['Allow']);
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
        assertEquals('text/csv, application/json', $this->headers['X-Acceptable']);
    }

    /**
     * @test
     */
    public function forceDownloadAddesContentDispositionHeaderWithGivenFilename()
    {
        $this->headers->forceDownload('example.csv');
        assertTrue(isset($this->headers['Content-Disposition']));
        assertEquals(
                'attachment; filename=example.csv',
                $this->headers['Content-Disposition']
        );
    }

    /**
     * @test
     */
    public function isIterable()
    {
        $this->headers->add('X-Foo', 'bar');
        foreach ($this->headers as $name => $value) {
            assertEquals('X-Foo', $name);
            assertEquals('bar', $value);
        }
    }

    /**
     * @test
     * @expectedException  BadMethodCallException
     */
    public function unsetViaArrayAccessThrowsBadMethodCallException()
    {
        $this->headers->add('X-Foo', 'bar');
        unset($this->headers['X-Foo']);
    }

    /**
     * @test
     * @group  issue_71
     * @since  5.1.0
     */
    public function cacheControlAddsCacheControlHeaderWithDefaultValue()
    {
        $this->headers->cacheControl();
        assertEquals('private', $this->headers[CacheControl::HEADER_NAME]);
    }

    /**
     * @test
     * @group  issue_71
     * @since  5.1.0
     */
    public function cacheControlReturnsCacheControlInstance()
    {
        assertInstanceOf(
                'stubbles\webapp\response\CacheControl',
                $this->headers->cacheControl()
        );
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
        assertEquals('example-request-id-foo', $this->headers['X-Request-ID']);
    }

    /**
     * @test
     * @since  5.1.0
     */
    public function ageAddsAgeHeader()
    {
        $this->headers->age(12);
        assertTrue(isset($this->headers['Age']));
        assertEquals(12, $this->headers['Age']);
    }
}
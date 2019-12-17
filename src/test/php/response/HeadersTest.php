<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\HttpUri;

use function bovigo\assert\{
    assertThat,
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
class HeadersTest extends TestCase
{
    /**
     * @var  Headers
     */
    private $headers;

    protected function setUp(): void
    {
        $this->headers = new Headers();
    }

    /**
     * @test
     */
    public function doesNotContainHeaderWhenNotAdded(): void
    {
        assertFalse($this->headers->contain('X-Foo'));
    }

    /**
     * @test
     */
    public function doesNotContainHeaderWhenNotAddedWithArrayAccess(): void
    {
        assertFalse(isset($this->headers['X-Foo']));
    }

    /**
     * @test
     */
    public function containsHeaderWhenAdded(): void
    {
        assertTrue(
                $this->headers->add('X-Foo', 'bar')->contain('X-Foo')
        );
    }

    /**
     * @test
     */
    public function containsHeaderWhenAddedWithArrayAccess(): void
    {
        $this->headers->add('X-Foo', 'bar');
        assertTrue(isset($this->headers['X-Foo']));
    }

    /**
     * @test
     */
    public function containsHeaderWhenAddedWithArrayAccess2(): void
    {
        $this->headers['X-Foo'] = 'bar';
        assertTrue(isset($this->headers['X-Foo']));
    }

    /**
     * @test
     */
    public function locationHeaderAcceptsUriAsString(): void
    {
        $this->headers->location('http://example.com/');
        assertTrue(isset($this->headers['Location']));
        assertThat($this->headers['Location'], equals('http://example.com/'));
    }

    /**
     * @test
     */
    public function locationHeaderAcceptsUriAsHttpUri(): void
    {
        $this->headers->location(HttpUri::fromString('http://example.com/'));
        assertTrue(isset($this->headers['Location']));
        assertThat($this->headers['Location'], equals('http://example.com/'));
    }

    /**
     * @test
     */
    public function allowAddsListOfAllowedMethods(): void
    {
        $this->headers->allow(['POST', 'PUT']);
        assertTrue(isset($this->headers['Allow']));
        assertThat($this->headers['Allow'], equals('POST, PUT'));
    }

    /**
     * @test
     */
    public function acceptableDoesNotAddListOfSupportedMimeTypesWhenListEmpty(): void
    {
        assertFalse(
                $this->headers->acceptable([])->contain('X-Acceptable')
        );
    }

    /**
     * @test
     */
    public function acceptableAddsListOfSupportedMimeTypesWhenListNotEmpty(): void
    {
        $this->headers->acceptable(['text/csv', 'application/json']);
        assertTrue(isset($this->headers['X-Acceptable']));
        assertThat($this->headers['X-Acceptable'], equals('text/csv, application/json'));
    }

    /**
     * @test
     */
    public function forceDownloadAddesContentDispositionHeaderWithGivenFilename(): void
    {
        $this->headers->forceDownload('example.csv');
        assertTrue(isset($this->headers['Content-Disposition']));
        assertThat(
                $this->headers['Content-Disposition'],
                equals('attachment; filename="example.csv"')
        );
    }

    /**
     * @test
     */
    public function isIterable(): void
    {
        $this->headers->add('X-Foo', 'bar');
        foreach ($this->headers as $name => $value) {
            assertThat($name, equals('X-Foo'));
            assertThat($value, equals('bar'));
        }
    }

    /**
     * @test
     */
    public function unsetViaArrayAccessThrowsBadMethodCallException(): void
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
    public function cacheControlAddsCacheControlHeaderWithDefaultValue(): void
    {
        $this->headers->cacheControl();
        assertThat($this->headers[CacheControl::HEADER_NAME], equals('private'));
    }

    /**
     * @test
     * @group  issue_71
     * @since  5.1.0
     */
    public function cacheControlReturnsCacheControlInstance(): void
    {
        assertThat($this->headers->cacheControl(), isInstanceOf(CacheControl::class));
    }

    /**
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdAddsRequestIdHeader(): void
    {
        $this->headers->requestId('example-request-id-foo');
        assertTrue(isset($this->headers['X-Request-ID']));
        assertThat($this->headers['X-Request-ID'], equals('example-request-id-foo'));
    }

    /**
     * @test
     * @since  5.1.0
     */
    public function ageAddsAgeHeader(): void
    {
        $this->headers->age(12);
        assertTrue(isset($this->headers['Age']));
        assertThat($this->headers['Age'], equals(12));
    }
}

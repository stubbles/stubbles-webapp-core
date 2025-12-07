<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 * @sicne  4.0.0
 */
#[Group('response')]
class HeadersTest extends TestCase
{
    private Headers $headers;

    protected function setUp(): void
    {
        $this->headers = new Headers();
    }

    #[Test]
    public function doesNotContainHeaderWhenNotAdded(): void
    {
        assertFalse($this->headers->contain('X-Foo'));
    }

    #[Test]
    public function doesNotContainHeaderWhenNotAddedWithArrayAccess(): void
    {
        assertFalse(isset($this->headers['X-Foo']));
    }

    #[Test]
    public function containsHeaderWhenAdded(): void
    {
        assertTrue(
            $this->headers->add('X-Foo', 'bar')->contain('X-Foo')
        );
    }

    #[Test]
    public function containsHeaderWhenAddedWithArrayAccess(): void
    {
        $this->headers->add('X-Foo', 'bar');
        assertTrue(isset($this->headers['X-Foo']));
    }

    #[Test]
    public function containsHeaderWhenAddedWithArrayAccess2(): void
    {
        $this->headers['X-Foo'] = 'bar';
        assertTrue(isset($this->headers['X-Foo']));
    }

    #[Test]
    public function locationHeaderAcceptsUriAsString(): void
    {
        $this->headers->location('http://example.com/');
        assertTrue(isset($this->headers['Location']));
        assertThat($this->headers['Location'], equals('http://example.com/'));
    }

    #[Test]
    public function locationHeaderAcceptsUriAsHttpUri(): void
    {
        $this->headers->location(HttpUri::fromString('http://example.com/'));
        assertTrue(isset($this->headers['Location']));
        assertThat($this->headers['Location'], equals('http://example.com/'));
    }

    #[Test]
    public function allowAddsListOfAllowedMethods(): void
    {
        $this->headers->allow(['POST', 'PUT']);
        assertTrue(isset($this->headers['Allow']));
        assertThat($this->headers['Allow'], equals('POST, PUT'));
    }

    #[Test]
    public function acceptableDoesNotAddListOfSupportedMimeTypesWhenListEmpty(): void
    {
        assertFalse(
            $this->headers->acceptable([])->contain('X-Acceptable')
        );
    }

    #[Test]
    public function acceptableAddsListOfSupportedMimeTypesWhenListNotEmpty(): void
    {
        $this->headers->acceptable(['text/csv', 'application/json']);
        assertTrue(isset($this->headers['X-Acceptable']));
        assertThat($this->headers['X-Acceptable'], equals('text/csv, application/json'));
    }

    #[Test]
    public function forceDownloadAddesContentDispositionHeaderWithGivenFilename(): void
    {
        $this->headers->forceDownload('example.csv');
        assertTrue(isset($this->headers['Content-Disposition']));
        assertThat(
            $this->headers['Content-Disposition'],
            equals('attachment; filename="example.csv"')
        );
    }

    #[Test]
    public function headersAreIterable(): void
    {
        $this->headers->add('X-Foo', 'bar');
        foreach ($this->headers as $name => $value) {
            assertThat($name, equals('X-Foo'));
            assertThat($value, equals('bar'));
        }
    }

    #[Test]
    public function unsetViaArrayAccessThrowsBadMethodCallException(): void
    {
        $this->headers->add('X-Foo', 'bar');
        expect(function() { unset($this->headers['X-Foo']); })
            ->throws(\BadMethodCallException::class);
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_71')]
    public function cacheControlAddsCacheControlHeaderWithDefaultValue(): void
    {
        $this->headers->cacheControl();
        assertThat($this->headers[CacheControl::HEADER_NAME], equals('private'));
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_71')]
    public function cacheControlReturnsCacheControlInstance(): void
    {
        assertThat($this->headers->cacheControl(), isInstanceOf(CacheControl::class));
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_74')]
    public function requestIdAddsRequestIdHeader(): void
    {
        $this->headers->requestId('example-request-id-foo');
        assertTrue(isset($this->headers['X-Request-ID']));
        assertThat($this->headers['X-Request-ID'], equals('example-request-id-foo'));
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    public function ageAddsAgeHeader(): void
    {
        $this->headers->age(12);
        assertTrue(isset($this->headers['Age']));
        assertThat($this->headers['Age'], equals(12));
    }
}

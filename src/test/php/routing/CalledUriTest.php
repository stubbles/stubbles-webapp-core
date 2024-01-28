<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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
    predicate\isSameAs
};
/**
 * Tests for stubbles\webapp\routing\CalledUri.
 *
 * @since  1.7.0
 */
#[Group('routing')]
class CalledUriTest extends TestCase
{
    private CalledUri $calledUri;
    private HttpUri&ClassProxy $httpUri;

    protected function setUp(): void
    {
        $this->httpUri   = NewInstance::stub(HttpUri::class);
        $this->calledUri = new CalledUri($this->httpUri, 'GET');
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function createInstanceWithEmptyRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(fn() => new CalledUri($this->httpUri, ''))
            ->throws(InvalidArgumentException::class);
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function castFromOtherInstanceReturnsInstance(): void
    {
        assertThat(
            CalledUri::castFrom($this->calledUri, null),
            isSameAs($this->calledUri)
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function castFromHttpUriInstanceReturnsInstance(): void
    {
        assertThat(
            CalledUri::castFrom($this->httpUri, 'GET'),
            equals($this->calledUri)
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function castFromHttpUriInstanceWithoutRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(function() { CalledUri::castFrom($this->httpUri, ''); })
            ->throws(InvalidArgumentException::class);
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function castFromHttpUriStringReturnsInstance(): void
    {

        assertThat(
            CalledUri::castFrom('http://example.net/', 'GET'),
            equals(new CalledUri('http://example.net/', 'GET'))
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function castFromHttpUriStringWithoutRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(function()  { CalledUri::castFrom('http://example.net/', ''); })
            ->throws(\InvalidArgumentException::class);
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function methodAlwaysEqualsNullMethod(): void
    {
        assertTrue($this->calledUri->methodEquals(null));
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function methodAlwaysEqualsEmptyMethod(): void
    {
        assertTrue($this->calledUri->methodEquals(''));
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function methodEqualsGivenMethod(): void
    {
        assertTrue($this->calledUri->methodEquals('GET'));
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function methodDoesNotEqualsGivenMethod(): void
    {
        assertFalse($this->calledUri->methodEquals('POST'));
    }

    public static function provideSatisfiedPathPattern(): Generator
    {
        yield ['/hello/mikey', '/hello/{name}$'];
        yield ['/hello/mikey/foo', '/hello/{name}'];
        yield ['/hello', '/hello'];
        yield ['/hello/world303', '/hello/[a-z0-9]+'];
        yield ['/', '/'];
        yield ['/hello', ''];
        yield ['/hello', null];
    }

    #[Test]
    #[DataProvider('provideSatisfiedPathPattern')]
    public function returnsTrueForSatisfiedPathPattern(
        string $path,
        ?string $pathPattern = null
    ): void {
        $this->httpUri->returns(['path' => $path]);
        assertTrue($this->calledUri->satisfiesPath($pathPattern));
    }

    public static function provideNonSatisfiedPathPattern(): Generator
    {
        yield ['/rss/articles', '/hello/{name}'];
        yield ['/hello/mikey', '/hello$'];
        yield ['/hello/', '/hello/{name}$'];
        yield ['/hello/mikey', '/$'];
        yield ['/hello/mikey', '$'];
    }

    #[Test]
    #[DataProvider('provideNonSatisfiedPathPattern')]
    public function returnsFalseForNonSatisfiedCondition(string $path, string $pathPattern): void
    {
        $this->httpUri->returns(['path' => $path]);
        assertFalse($this->calledUri->satisfiesPath($pathPattern));
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function isHttpsWhenRequestUriHasHttps(): void
    {
        $this->httpUri->returns(['isHttps' => true]);
        assertTrue($this->calledUri->isHttps());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function toHttpReturnsTransformedUri(): void
    {
        $httpUri = NewInstance::stub(HttpUri::class);
        $this->httpUri->returns(['toHttp' => $httpUri]);
        assertThat($this->calledUri->toHttp(), isSameAs($httpUri));
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function toHttpsReturnsTransformedUri(): void
    {
        $httpUri = NewInstance::stub(HttpUri::class);
        $this->httpUri->returns(['toHttps' => $httpUri]);
        assertThat($this->calledUri->toHttps(), isSameAs($httpUri));
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function returnsStringRepresentationOfUri(): void
    {
        $this->httpUri->returns(['__toString' => 'http://example.net/foo/bar']);
        assertThat((string) $this->calledUri, equals('http://example.net/foo/bar'));
    }
}

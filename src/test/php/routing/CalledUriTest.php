<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
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
 * Tests for stubbles\webapp\CalledUri.
 *
 * @since  1.7.0
 * @group  routing
 */
class CalledUriTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\CalledUri
     */
    private $calledUri;
    /**
     * @var  HttpUri&\bovigo\callmap\ClassProxy
     */
    private $httpUri;

    protected function setUp(): void
    {
        $this->httpUri   = NewInstance::stub(HttpUri::class);
        $this->calledUri = new CalledUri($this->httpUri, 'GET');
    }

    /**
     * @since  4.0.0
     * @test
     */
    public function createInstanceWithEmptyRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(function() { new CalledUri($this->httpUri, ''); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromOtherInstanceReturnsInstance(): void
    {
        assertThat(
                CalledUri::castFrom($this->calledUri, null),
                isSameAs($this->calledUri)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceReturnsInstance(): void
    {
        assertThat(
                CalledUri::castFrom($this->httpUri, 'GET'),
                equals($this->calledUri)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriInstanceWithoutRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(function() { CalledUri::castFrom($this->httpUri, ''); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriStringReturnsInstance(): void
    {

        assertThat(
                CalledUri::castFrom('http://example.net/', 'GET'),
                equals(new CalledUri('http://example.net/', 'GET'))
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castFromHttpUriStringWithoutRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(function()  { CalledUri::castFrom('http://example.net/', ''); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsNullMethod(): void
    {
        assertTrue($this->calledUri->methodEquals(null));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodAlwaysEqualsEmptyMethod(): void
    {
        assertTrue($this->calledUri->methodEquals(''));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodEqualsGivenMethod(): void
    {
        assertTrue($this->calledUri->methodEquals('GET'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodDoesNotEqualsGivenMethod(): void
    {
        assertFalse($this->calledUri->methodEquals('POST'));
    }

    /**
     * @return  array<mixed[]>
     */
    public function provideSatisfiedPathPattern(): array
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
    public function returnsTrueForSatisfiedPathPattern(string $path, string $pathPattern = null): void
    {
        $this->httpUri->returns(['path' => $path]);
        assertTrue($this->calledUri->satisfiesPath($pathPattern));
    }

    public function provideNonSatisfiedPathPattern(): array
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
    public function returnsFalseForNonSatisfiedCondition(string $path, string $pathPattern): void
    {
        $this->httpUri->returns(['path' => $path]);
        assertFalse($this->calledUri->satisfiesPath($pathPattern));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function isHttpsWhenRequestUriHasHttps(): void
    {
        $this->httpUri->returns(['isHttps' => true]);
        assertTrue($this->calledUri->isHttps());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpReturnsTransformedUri(): void
    {
        $httpUri = NewInstance::stub(HttpUri::class);
        $this->httpUri->returns(['toHttp' => $httpUri]);
        assertThat($this->calledUri->toHttp(), isSameAs($httpUri));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function toHttpsReturnsTransformedUri(): void
    {
        $httpUri = NewInstance::stub(HttpUri::class);
        $this->httpUri->returns(['toHttps' => $httpUri]);
        assertThat($this->calledUri->toHttps(), isSameAs($httpUri));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function returnsStringRepresentationOfUri(): void
    {
        $this->httpUri->returns(['__toString' => 'http://example.net/foo/bar']);
        assertThat((string) $this->calledUri, equals('http://example.net/foo/bar'));
    }
}

<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\UriPath.
 *
 * @since  2.0.0
 */
#[Group('core')]
class UriPathTest extends TestCase
{
    private UriPath $uriPath;

    protected function setUp(): void
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world/foo');
    }

    #[Test]
    public function returnsGivenConfiguredPath(): void
    {
        assertThat($this->uriPath->configured(), equals('/hello/{name}'));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function returnsGivenActualPath(): void
    {
        assertThat($this->uriPath->actual(), equals('/hello/world/foo'));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function castingToStringReturnsActualPath(): void
    {
        assertThat((string) $this->uriPath, equals('/hello/world/foo'));
    }

    public static function providePathArguments(): Generator
    {
        yield ['/hello/mikey', '/hello/{name}', ['name' => 'mikey']];
        yield ['/hello/303/mikey', '/hello/{id}/{name}', ['id' => '303', 'name' => 'mikey']];
    }

    #[Test]
    #[DataProvider('providePathArguments')]
    public function returnsPathArguments(
        string $calledPath,
        string $configuredPath,
        array $expectedArguments
    ): void {
        $uriPath = new UriPath($configuredPath, $calledPath);
        foreach ($expectedArguments as $name => $value) {
            assertTrue($uriPath->hasArgument($name));
            assertThat($uriPath->readArgument($name)->unsecure(), equals($value));
        }
    }

    #[Test]
    public function doesNotHaveNonGivenArgument(): void
    {
        assertFalse($this->uriPath->hasArgument('id'));
    }

    #[Test]
    public function returnsNullForNonGivenArgument(): void
    {
        assertNull($this->uriPath->readArgument('id')->unsecure());
    }

    public static function provideRemainingPath(): Generator
    {
        yield ['/hello/mikey', '/hello/{name}', null];
        yield ['/hello/303/mikey', '/hello/{id}/{name}', ''];
        yield ['/hello/303/mikey/foo', '/hello/{id}/{name}', '/foo'];
        yield ['/hello', '/hello', ''];
        yield ['/hello/world;name', '/hello/[a-z0-9]+;?', 'name'];
        yield ['/hello/world', '/hello/?', 'world'];
        yield ['/', '/', ''];
    }

    #[Test]
    #[DataProvider('provideRemainingPath')]
    public function returnsRemainingPath(
        string $calledPath,
        string $configuredPath,
        ?string $expected
    ): void {
        $uriPath = new UriPath($configuredPath, $calledPath);
        assertThat($uriPath->remaining(), equals($expected));
    }

    #[Test]
    public function returnsDefaultIfRemainingPathIsNull(): void
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world');
        assertThat($this->uriPath->remaining('index.html'), equals('index.html'));
    }
}

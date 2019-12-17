<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;
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
 * @group  core
 */
class UriPathTest extends TestCase
{
    /**
     * instance to test
     *
     * @var  UriPath
     */
    private $uriPath;

    protected function setUp(): void
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world/foo');
    }

    /**
     * @test
     */
    public function returnsGivenConfiguredPath(): void
    {
        assertThat($this->uriPath->configured(), equals('/hello/{name}'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function returnsGivenActualPath(): void
    {
        assertThat($this->uriPath->actual(), equals('/hello/world/foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castingToStringReturnsActualPath(): void
    {
        assertThat((string) $this->uriPath, equals('/hello/world/foo'));
    }

    /**
     * @return  array<mixed[]>
     */
    public function providePathArguments(): array
    {
        return [['/hello/mikey', '/hello/{name}', ['name' => 'mikey']],
                ['/hello/303/mikey', '/hello/{id}/{name}', ['id' => '303', 'name' => 'mikey']]
        ];
    }

    /**
     * @param  string                $calledPath
     * @param  string                $configuredPath
     * @param  array<string,string>  $expectedArguments
     * @test
     * @dataProvider  providePathArguments
     */
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

    /**
     * @test
     */
    public function doesNotHaveNonGivenArgument(): void
    {
        assertFalse($this->uriPath->hasArgument('id'));
    }

    /**
     * @test
     */
    public function returnsNullForNonGivenArgument(): void
    {
        assertNull($this->uriPath->readArgument('id')->unsecure());
    }

    /**
     * @return  array<mixed[]>
     */
    public function provideRemainingPath(): array
    {
        return [['/hello/mikey', '/hello/{name}', null],
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
     * @dataProvider  provideRemainingPath
     */
    public function returnsRemainingPath(
            string $calledPath,
            string $configuredPath,
            ?string $expected
    ): void {
        $uriPath = new UriPath($configuredPath, $calledPath);
        assertThat($uriPath->remaining(), equals($expected));
    }

    /**
     * @test
     */
    public function returnsDefaultIfRemainingPathIsNull(): void
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world');
        assertThat($this->uriPath->remaining('index.html'), equals('index.html'));
    }
}

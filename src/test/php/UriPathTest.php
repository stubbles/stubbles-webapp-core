<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use function bovigo\assert\assert;
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
class UriPathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  UriPath
     */
    private $uriPath;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world/foo');
    }

    /**
     * @test
     */
    public function returnsGivenConfiguredPath()
    {
        assert($this->uriPath->configured(), equals('/hello/{name}'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function returnsGivenActualPath()
    {
        assert($this->uriPath->actual(), equals('/hello/world/foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castingToStringReturnsActualPath()
    {
        assert((string) $this->uriPath, equals('/hello/world/foo'));
    }

    /**
     * data provider for satisfying path pattern tests
     *
     * @return  array
     */
    public function providePathArguments()
    {
        return [['/hello/mikey', '/hello/{name}', ['name' => 'mikey']],
                ['/hello/303/mikey', '/hello/{id}/{name}', ['id' => '303', 'name' => 'mikey']]
        ];
    }

    /**
     * @test
     * @dataProvider  providePathArguments
     */
    public function returnsPathArguments($calledPath, $configuredPath, array $expectedArguments)
    {
        $uriPath = new UriPath($configuredPath, $calledPath);
        foreach ($expectedArguments as $name => $value) {
            assertTrue($uriPath->hasArgument($name));
            assert($uriPath->readArgument($name)->unsecure(), equals($value));
        }
    }

    /**
     * @test
     */
    public function doesNotHaveNonGivenArgument()
    {
        assertFalse($this->uriPath->hasArgument('id'));
    }

    /**
     * @test
     */
    public function returnsNullForNonGivenArgument()
    {
        assertNull($this->uriPath->readArgument('id')->unsecure());
    }

    /**
     * data provider for remaining path tests
     *
     * @return  array
     */
    public function provideRemainingPath()
    {
        return [['/hello/mikey', '/hello/{name}', null],
                ['/hello/303/mikey', '/hello/{id}/{name}', null],
                ['/hello/303/mikey/foo', '/hello/{id}/{name}', '/foo'],
                ['/hello', '/hello', null],
                ['/hello/world;name', '/hello/[a-z0-9]+;?', 'name'],
                ['/hello/world', '/hello/?', 'world'],
                ['/', '/', null]
        ];
    }

    /**
     * @test
     * @dataProvider  provideRemainingPath
     */
    public function returnsRemainingPath($calledPath, $configuredPath, $expected)
    {
        $uriPath = new UriPath($configuredPath, $calledPath);
        assert($uriPath->remaining(), equals($expected));
    }

    /**
     * @test
     */
    public function returnsDefaultIfRemainingPathIsNull()
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world');
        assert($this->uriPath->remaining('index.html'), equals('index.html'));
    }
}

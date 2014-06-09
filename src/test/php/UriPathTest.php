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
        $this->assertEquals('/hello/{name}', $this->uriPath->configured());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function returnsGivenActualPath()
    {
        $this->assertEquals('/hello/world/foo', $this->uriPath->actual());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function castingToStringReturnsActualPath()
    {
        $this->assertEquals('/hello/world/foo', (string) $this->uriPath);
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
            $this->assertTrue($uriPath->hasArgument($name));
            $this->assertEquals($value, $uriPath->readArgument($name)->unsecure());
        }
    }

    /**
     * @test
     */
    public function doesNotHaveNonGivenArgument()
    {
        $this->assertFalse($this->uriPath->hasArgument('id'));
    }

    /**
     * @test
     */
    public function returnsNullForNonGivenArgument()
    {
        $this->assertNull($this->uriPath->readArgument('id')->unsecure());
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
        $this->assertEquals(
                $expected,
                $uriPath->remaining()
        );
    }

    /**
     * @test
     */
    public function returnsDefaultIfRemainingPathIsNull()
    {
        $this->uriPath = new UriPath('/hello/{name}', '/hello/world');
        $this->assertEquals('index.html', $this->uriPath->remaining('index.html'));
    }
}

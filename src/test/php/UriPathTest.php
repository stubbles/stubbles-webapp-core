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
        $this->uriPath = new UriPath('/hello/{name}', ['name' => 'world'], '/foo');
    }

    /**
     * @test
     */
    public function returnsGivenMatchedPath()
    {
        $this->assertEquals('/hello/{name}', $this->uriPath->getMatched());
    }

    /**
     * @test
     */
    public function hasGivenArgument()
    {
        $this->assertTrue($this->uriPath->hasArgument('name'));
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
     * @since  3.3.0
     * @group  issue_41
     */
    public function readsGivenArgument()
    {
        $this->assertEquals('world', $this->uriPath->readArgument('name')->asString());
    }

    /**
     * @test
     * @since  3.3.0
     * @group  issue_41
     */
    public function readsNullForNonGivenArgument()
    {
        $this->assertNull($this->uriPath->readArgument('id')->unsecure());
    }

    /**
     * @test
     * @since  3.3.0
     * @group  issue_41
     */
    public function readsDefaultForGivenArgument()
    {
        $this->assertEquals(303, $this->uriPath->readArgument('id', 303)->asInt());
    }

    /**
     * @test
     */
    public function returnsGivenRemainingPath()
    {
        $this->assertEquals('/foo', $this->uriPath->remaining());
    }

    /**
     * @test
     */
    public function returnsNullIfRemainingPathIsNull()
    {
        $this->uriPath = new UriPath('/hello/{name}', ['name' => 'world'], null);
        $this->assertNull($this->uriPath->remaining());
    }

    /**
     * @test
     */
    public function returnsDefaultIfRemainingPathIsNull()
    {
        $this->uriPath = new UriPath('/hello/{name}', ['name' => 'world'], null);
        $this->assertEquals('index.html', $this->uriPath->remaining('index.html'));
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
        $uriPath = UriPath::from($configuredPath, $calledPath);
        foreach ($expectedArguments as $name => $value) {
            $this->assertTrue($uriPath->hasArgument($name));
            $this->assertEquals($value, $uriPath->readArgument($name)->unsecure());
        }
    }

    /**
     * data provider for remaining uri tests
     *
     * @return  array
     */
    public function provideRemainingUris()
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
     * @dataProvider  provideRemainingUris
     */
    public function returnsRemainingUri($calledPath, $configuredPath, $expected)
    {
        $this->assertEquals(
                $expected,
                UriPath::from($configuredPath, $calledPath)->remaining()
        );
    }
}

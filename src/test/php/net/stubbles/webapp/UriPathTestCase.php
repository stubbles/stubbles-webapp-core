<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
/**
 * Tests for net\stubbles\webapp\UriPath.
 *
 * @since  2.0.0
 * @group  core
 */
class UriPathTestCase extends \PHPUnit_Framework_TestCase
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
        $this->uriPath = new UriPath('/hello/{name}', array('name' => 'world'), '/foo');
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
     */
    public function returnsGivenArgument()
    {
        $this->assertEquals('world', $this->uriPath->getArgument('name'));
    }

    /**
     * @test
     */
    public function returnsNullForNonGivenArgument()
    {
        $this->assertNull($this->uriPath->getArgument('id'));
    }

    /**
     * @test
     */
    public function returnsDefaultForGivenArgument()
    {
        $this->assertEquals(303, $this->uriPath->getArgument('id', 303));
    }

    /**
     * @test
     */
    public function returnsGivenRemainingPath()
    {
        $this->assertEquals('/foo', $this->uriPath->getRemaining());
    }
}
?>
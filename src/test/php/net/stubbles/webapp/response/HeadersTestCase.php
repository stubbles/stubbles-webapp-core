<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
/**
 * Tests for net\stubbles\webapp\response\Headers.
 *
 * @group  response
 * @sicne  3.5.0
 */
class HeadersTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  Headers
     */
    private $headers;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->headers = new Headers();
    }

    /**
     * @test
     */
    public function doesNotContainHeaderWhenNotAdded()
    {
        $this->assertFalse($this->headers->contain('X-Foo'));
    }

    /**
     * @test
     */
    public function containsHeaderWhenAdded()
    {
        $this->assertTrue(
                $this->headers->add('X-Foo', 'bar')
                              ->contain('X-Foo')
        );
    }

    /**
     * @test
     */
    public function isIterable()
    {
        $this->headers->add('X-Foo', 'bar');
        foreach ($this->headers as $name => $value) {
            $this->assertEquals('X-Foo', $name);
            $this->assertEquals('bar', $value);
        }
    }
}
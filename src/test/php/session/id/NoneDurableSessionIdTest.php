<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session\id;
/**
 * Tests for stubbles\webapp\session\id\NoneDurableSessionId.
 *
 * @since  2.0.0
 * @group  session
 * @group  id
 */
class NoneDurableSessionIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NoneDurableSessionId
     */
    private $noneDurableSessionId;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->noneDurableSessionId = new NoneDurableSessionId();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function sessionNameStaysSameForInstance()
    {
        $this->assertEquals(
                $this->noneDurableSessionId->name(),
                $this->noneDurableSessionId->name()
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function sessionNameIsDifferentForDifferentInstances()
    {
        $other = new NoneDurableSessionId();
        $this->assertNotEquals(
                $this->noneDurableSessionId->name(),
                $other->name()
        );
    }

    /**
     * @test
     */
    public function hasSessionId()
    {
        $this->assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            (string) $this->noneDurableSessionId
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId()
    {
        $previous = (string) $this->noneDurableSessionId;
        $this->assertNotEquals($previous,
                               (string) $this->noneDurableSessionId->regenerate()
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid()
    {
        $this->assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            (string) $this->noneDurableSessionId->regenerate()
        );
    }

    /**
     * @test
     */
    public function invalidateDoesNothing()
    {
        $this->assertSame($this->noneDurableSessionId,
                          $this->noneDurableSessionId->invalidate()
        );
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionNameWhenProvided()
    {
        $this->assertEquals(
                'foo',
                (new NoneDurableSessionId('foo'))->name()
        );
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionIdWhenProvided()
    {
        $this->assertEquals(
                '313',
                (string) new NoneDurableSessionId('foo', '313')
        );
    }
}

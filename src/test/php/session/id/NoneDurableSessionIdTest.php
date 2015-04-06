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
        assertEquals(
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
        assertNotEquals(
                $this->noneDurableSessionId->name(),
                $other->name()
        );
    }

    /**
     * @test
     */
    public function hasSessionId()
    {
        assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            (string) $this->noneDurableSessionId
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId()
    {
        $previous = (string) $this->noneDurableSessionId;
        assertNotEquals($previous,
                               (string) $this->noneDurableSessionId->regenerate()
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid()
    {
        assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            (string) $this->noneDurableSessionId->regenerate()
        );
    }

    /**
     * @test
     */
    public function invalidateDoesNothing()
    {
        assertSame($this->noneDurableSessionId,
                          $this->noneDurableSessionId->invalidate()
        );
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionNameWhenProvided()
    {
        assertEquals(
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
        assertEquals(
                '313',
                (string) new NoneDurableSessionId('foo', '313')
        );
    }
}

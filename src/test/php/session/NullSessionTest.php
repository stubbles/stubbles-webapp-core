<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session;
/**
 * Tests for stubbles\webapp\session\NullSession.
 *
 * @since  2.0.0
 * @group  session
 */
class NullSessionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NullSession
     */
    private $nullSession;
    /**
     * mocked session id
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionId;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->sessionId   = $this->getMock('stubbles\webapp\session\id\SessionId');
        $this->nullSession = new NullSession($this->sessionId);
    }

    /**
     * @test
     */
    public function isAlwaysNew()
    {
        assertTrue($this->nullSession->isNew());
    }

    /**
     * @test
     */
    public function idIsSessionId()
    {
        $this->sessionId->method('__toString')->will(returnValue('303'));
        assertEquals('303', $this->nullSession->id());
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        $this->sessionId->expects(once())->method('regenerate');
        assertEquals(
                $this->nullSession,
                $this->nullSession->regenerateId()
        );
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->sessionId->method('name')->will(returnValue('foo'));
        assertEquals('foo', $this->nullSession->name());
    }

    /**
     * @test
     */
    public function isAlwaysValid()
    {
        assertTrue($this->nullSession->isValid());
    }

    /**
     * @test
     */
    public function invalidateInvalidatesSessionId()
    {
        $this->sessionId->expects(once())->method('invalidate');
        assertEquals(
                $this->nullSession,
                $this->nullSession->invalidate()
        );
    }

    /**
     * @test
     */
    public function hasNeverAnyValue()
    {
        assertFalse($this->nullSession->hasValue('foo'));
    }

    /**
     * @test
     */
    public function neverReturnsValue()
    {
        assertNull($this->nullSession->value('foo'));
    }

    /**
     * @test
     */
    public function alwaysReturnsDefaultValue()
    {
        assertEquals('bar', $this->nullSession->value('foo', 'bar'));
    }

    /**
     * @test
     */
    public function putValueDoesNothing()
    {
        assertEquals(
                $this->nullSession,
                $this->nullSession->putValue('foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function removeAlwaysTellsValueWasNotPresent()
    {
        assertFalse($this->nullSession->removeValue('foo'));
    }

    /**
     * @test
     */
    public function hasNoValueKeys()
    {
        assertEquals([], $this->nullSession->valueKeys());
    }
}

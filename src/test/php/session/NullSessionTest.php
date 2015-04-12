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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
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
     * @type  \stubbles\webapp\session\NullSession
     */
    private $nullSession;
    /**
     * mocked session id
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $sessionId;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->sessionId   = NewInstance::of('stubbles\webapp\session\id\SessionId');
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
        $this->sessionId->mapCalls(['__toString' => '303']);
        assertEquals('303', $this->nullSession->id());
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        assertEquals(
                $this->nullSession,
                $this->nullSession->regenerateId()
        );
        callmap\verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->sessionId->mapCalls(['name' => 'foo']);
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
        assertEquals(
                $this->nullSession,
                $this->nullSession->invalidate()
        );
        callmap\verify($this->sessionId, 'invalidate')->wasCalledOnce();
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

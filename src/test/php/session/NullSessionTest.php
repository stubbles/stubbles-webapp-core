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
use bovigo\callmap\NewInstance;
use stubbles\webapp\session\id\SessionId;

use function bovigo\assert\assert;
use function bovigo\assert\assertEmptyArray;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\verify;
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
        $this->sessionId   = NewInstance::of(SessionId::class);
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
        assert($this->nullSession->id(), equals('303'));
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        assert(
                $this->nullSession->regenerateId(),
                equals($this->nullSession)
        );
        verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->sessionId->mapCalls(['name' => 'foo']);
        assert($this->nullSession->name(), equals('foo'));
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
        assert(
                $this->nullSession->invalidate(),
                equals($this->nullSession)
        );
        verify($this->sessionId, 'invalidate')->wasCalledOnce();
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
        assert($this->nullSession->value('foo', 'bar'), equals('bar'));
    }

    /**
     * @test
     */
    public function putValueDoesNothing()
    {
        assert(
                $this->nullSession->putValue('foo', 'bar'),
                equals($this->nullSession)
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
        assertEmptyArray($this->nullSession->valueKeys());
    }
}

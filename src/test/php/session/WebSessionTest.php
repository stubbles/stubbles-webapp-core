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
 * Tests for stubbles\webapp\session\WebSession.
 *
 * @group  session
 */
class WebSessionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked session storage
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionStorage;
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
        $this->sessionStorage = $this->getMock('stubbles\webapp\session\storage\SessionStorage');
        $this->sessionId      = $this->getMock('stubbles\webapp\session\id\SessionId');
    }

    /**
     * creates valid web session
     *
     * @param   string  $givenFingerprint
     * @param   string  $storageFingerprint
     * @return  \stubbles\webapp\session\WebSession
     */
    private function createWebSession($givenFingerprint = 'aFingerprint',
                                      $storageFingerprint = 'aFingerprint')
    {
        $this->sessionStorage->method('hasValue')
                ->will(returnValue(null !== $storageFingerprint));
        $this->sessionStorage->method('value')
                ->will(returnValue($storageFingerprint));
        return new WebSession(
                $this->sessionStorage,
                $this->sessionId,
                $givenFingerprint
        );
    }

    /**
     * @test
     */
    public function isNewWhenSessionContainsNoFingerprint()
    {
        assertTrue($this->createWebSession('aFingerprint', null)->isNew());
    }

    /**
     * @test
     */
    public function regeneratesSessionIdWhenSessionIsNew()
    {
        $this->sessionId->expects(once())->method('regenerate');
        $this->createWebSession('aFingerprint', null);
    }

    /**
     * @test
     */
    public function storesFingerPrintWhenSessionIsNew()
    {
        $this->sessionStorage->expects(once())
                ->method('putValue')
                ->with(equalTo(Session::FINGERPRINT), equalTo('aFingerprint'));
        $this->createWebSession('aFingerprint', null);
    }

    /**
     * @test
     */
    public function regeneratesSessionIdWhenSessionIsHijacked()
    {
        $this->sessionId->expects(once())->method('regenerate');
        $this->createWebSession('otherFingerprint');
    }

    /**
     * @test
     */
    public function clearsSessionDataWhenSessionIsHijacked()
    {
        $this->sessionStorage->expects(once())->method('clear');
        $this->createWebSession('otherFingerprint');
    }

    /**
     * @test
     */
    public function storesGivenFingerPrintWhenSessionIsHijacked()
    {
        $this->sessionStorage->expects(once())
                ->method('putValue')
                ->with(equalTo(Session::FINGERPRINT), equalTo('otherFingerprint'));
        $this->createWebSession('otherFingerprint');
    }

    /**
     * @test
     */
    public function idIsSessionId()
    {
        $this->sessionId->method('__toString')->will(returnValue('303'));
        assertEquals('303', $this->createWebSession()->id());
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        $webSession = $this->createWebSession();
        $this->sessionId->expects(once())->method('regenerate');
        assertEquals($webSession, $webSession->regenerateId());
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->sessionId->method('name')->will(returnValue('foo'));
        assertEquals('foo', $this->createWebSession()->name());
    }

    /**
     * @test
     */
    public function isValidByDefault()
    {
        assertTrue($this->createWebSession()->isValid());
    }

    /**
     * creates invalid web session
     *
     * A web session is invalid if the fingerprint is removed. This is simulated
     * in this case by simply saying that no fingerprint is present on the
     * second request to the storage.
     *
     * @return  \stubbles\webapp\session\WebSession
     */
    private function createInvalidWebSession()
    {
        $this->sessionStorage->method('hasValue')
                ->will(onConsecutiveCalls(true, false));
        $this->sessionStorage->method('value')
                ->will(returnValue('aFingerprint'));
        return new WebSession(
                $this->sessionStorage,
                $this->sessionId,
                'aFingerprint'
        );
    }
    /**
     * @test
     */
    public function isNotValidWhenFingerprintIsRemoved()
    {
        assertFalse($this->createInvalidWebSession()->isValid());
    }

    /**
     * @test
     */
    public function invalidateClearsSessionData()
    {
        $webSession = $this->createWebSession();
        $this->sessionStorage->expects(once())->method('clear');
        assertEquals($webSession, $webSession->invalidate());
    }

    /**
     * @test
     */
    public function invalidateInvalidatesSessionId()
    {
        $webSession = $this->createWebSession();
        $this->sessionId->expects(once())->method('invalidate');
        assertEquals($webSession, $webSession->invalidate());
    }

    /**
     * @test
     */
    public function hasValueReturnsFalseOnInvalidSession()
    {
        assertFalse($this->createInvalidWebSession()->hasValue('foo'));
    }

    /**
     * creates valid web session with value expectations
     *
     * @param   string  $value
     * @return  \stubbles\webapp\session\WebSession
     */
    private function createWebSessionWithValues($value = null)
    {
        $this->sessionStorage->method('hasValue')
                ->will(onConsecutiveCalls(true, true, null !== $value));
        $this->sessionStorage->method('value')
                ->will(onConsecutiveCalls('aFingerprint', $value));
        return new WebSession(
                $this->sessionStorage,
                $this->sessionId,
                'aFingerprint'
        );
    }

    /**
     * @test
     */
    public function hasNeverAnyValueByDefault()
    {
        assertFalse($this->createWebSessionWithValues()->hasValue('foo'));
    }

    /**
     * @test
     */
    public function hasValueIfStored()
    {
        assertTrue($this->createWebSessionWithValues('bar')->hasValue('foo'));
    }

    /**
     * @test
     */
    public function getValueReturnsNullIfValueNotStored()
    {
        assertNull($this->createWebSessionWithValues()->value('foo'));
    }

    /**
     * @test
     */
    public function getValueReturnsDefaultIfValueNotStoredAndDefaultGiven()
    {
        assertEquals(
                'bar',
                $this->createWebSessionWithValues()->value('foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function getValueReturnsStoredValue()
    {
        assertEquals(
                'baz',
                $this->createWebSessionWithValues('baz')->value('foo', 'bar')
        );
    }

    /**
     * @test
     * @expectedException  LogicException
     */
    public function getValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->value('foo');
    }

    /**
     * @test
     */
    public function putValueStoresValue()
    {
        $this->sessionStorage->expects(once())
                ->method('putValue')
                ->with(equalTo('foo'), equalTo('bar'));
        $webSession = $this->createWebSession();
        assertEquals($webSession, $webSession->putValue('foo', 'bar'));
    }

    /**
     * @test
     * @expectedException  LogicException
     */
    public function putValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->putValue('foo', 'bar');
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfValueWasNotStoredBefore()
    {
        assertFalse($this->createWebSessionWithValues()->removeValue('foo'));
    }

    /**
     * @test
     */
    public function removeReturnsTrueIfValueWasNotStoredBefore()
    {
        $this->sessionStorage->expects(once())
                ->method('removeValue')
                ->with(equalTo('foo'));
        assertTrue($this->createWebSessionWithValues('bar')->removeValue('foo'));
    }

    /**
     * @test
     * @expectedException  LogicException
     */
    public function removeValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->removeValue('foo');
    }

    /**
     * @test
     */
    public function getValueKeysReturnsAllKeysWithoutFingerprint()
    {
        $this->sessionStorage->method('valueKeys')
                ->will(returnValue([Session::FINGERPRINT, 'foo']));
        assertEquals(
                ['foo'],
                $this->createWebSession()->valueKeys()
        );
    }

    /**
     * @test
     * @expectedException  LogicException
     */
    public function getValueKeysThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->valueKeys();
    }
}

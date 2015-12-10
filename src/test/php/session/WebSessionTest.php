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
use stubbles\webapp\session\id\SessionId;
use stubbles\webapp\session\storage\SessionStorage;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $sessionStorage;
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
        $this->sessionStorage = NewInstance::of(SessionStorage::class);
        $this->sessionId      = NewInstance::of(SessionId::class);
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
        $this->sessionStorage->mapCalls(
                ['hasValue' => null !== $storageFingerprint,
                 'value'    => $storageFingerprint
                ]
        );
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
        $this->createWebSession('aFingerprint', null);
        callmap\verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesFingerPrintWhenSessionIsNew()
    {
        $this->createWebSession('aFingerprint', null);
        callmap\verify($this->sessionStorage, 'putValue')
                ->received(Session::FINGERPRINT, 'aFingerprint');
    }

    /**
     * @test
     */
    public function regeneratesSessionIdWhenSessionIsHijacked()
    {
        $this->createWebSession('otherFingerprint');
        callmap\verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function clearsSessionDataWhenSessionIsHijacked()
    {
        $this->createWebSession('otherFingerprint');
        callmap\verify($this->sessionStorage, 'clear')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesGivenFingerPrintWhenSessionIsHijacked()
    {
        $this->createWebSession('otherFingerprint');
        callmap\verify($this->sessionStorage, 'putValue')
                ->received(Session::FINGERPRINT, 'otherFingerprint');
    }

    /**
     * @test
     */
    public function idIsSessionId()
    {
        $this->sessionId->mapCalls(['__toString' => '303']);
        assertEquals('303', $this->createWebSession()->id());
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        $webSession = $this->createWebSession();
        assertEquals($webSession, $webSession->regenerateId());
        callmap\verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->sessionId->mapCalls(['name' => 'foo']);
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
        $this->sessionStorage->mapCalls(
                ['hasValue' => callmap\onConsecutiveCalls(true, false),
                 'value'    => 'aFingerprint'
                ]
        );
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
        assertEquals($webSession, $webSession->invalidate());
        callmap\verify($this->sessionStorage, 'clear')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function invalidateInvalidatesSessionId()
    {
        $webSession = $this->createWebSession();
        assertEquals($webSession, $webSession->invalidate());
        callmap\verify($this->sessionId, 'invalidate')->wasCalledOnce();
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
        $this->sessionStorage->mapCalls(
                ['hasValue' => function($key) use ($value)
                        {
                            if (Session::FINGERPRINT === $key) {
                                return true;
                            }

                            return null !== $value;
                        },
                 'value'    => callmap\onConsecutiveCalls('aFingerprint', $value)
                ]
        );
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
        $webSession = $this->createWebSession();
        assertEquals($webSession, $webSession->putValue('foo', 'bar'));
        callmap\verify($this->sessionStorage, 'putValue')
                ->received('foo', 'bar');
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
        assertTrue($this->createWebSessionWithValues('bar')->removeValue('foo'));
        callmap\verify($this->sessionStorage, 'removeValue')->received('foo');
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
        $session = $this->createWebSession();
        $this->sessionStorage->mapCalls(
                ['hasValue' => true, 'valueKeys' => [Session::FINGERPRINT, 'foo']]
        );
        assertEquals(
                ['foo'],
                $session->valueKeys()
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

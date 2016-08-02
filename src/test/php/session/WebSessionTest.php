<?php
declare(strict_types=1);
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
use stubbles\webapp\session\storage\SessionStorage;

use function bovigo\assert\{
    assert,
    assertFalse,
    assertNull,
    assertTrue,
    expect,
    predicate\equals
};
use function bovigo\callmap\onConsecutiveCalls;
use function bovigo\callmap\verify;
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

    private function createWebSession(
            string $givenFingerprint = 'aFingerprint',
            $storageFingerprint      = 'aFingerprint'
    ): WebSession {
        $this->sessionStorage->mapCalls([
                'hasValue' => null !== $storageFingerprint,
                'value'    => $storageFingerprint
        ]);
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
        verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesFingerPrintWhenSessionIsNew()
    {
        $this->createWebSession('aFingerprint', null);
        verify($this->sessionStorage, 'putValue')
                ->received(Session::FINGERPRINT, 'aFingerprint');
    }

    /**
     * @test
     */
    public function regeneratesSessionIdWhenSessionIsHijacked()
    {
        $this->createWebSession('otherFingerprint');
        verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function clearsSessionDataWhenSessionIsHijacked()
    {
        $this->createWebSession('otherFingerprint');
        verify($this->sessionStorage, 'clear')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesGivenFingerPrintWhenSessionIsHijacked()
    {
        $this->createWebSession('otherFingerprint');
        verify($this->sessionStorage, 'putValue')
                ->received(Session::FINGERPRINT, 'otherFingerprint');
    }

    /**
     * @test
     */
    public function idIsSessionId()
    {
        $this->sessionId->mapCalls(['__toString' => '303']);
        assert($this->createWebSession()->id(), equals('303'));
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        $webSession = $this->createWebSession();
        assert($webSession->regenerateId(), equals($webSession));
        verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->sessionId->mapCalls(['name' => 'foo']);
        assert($this->createWebSession()->name(), equals('foo'));
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
    private function createInvalidWebSession(): WebSession
    {
        $this->sessionStorage->mapCalls([
                'hasValue' => onConsecutiveCalls(true, false),
                'value'    => 'aFingerprint'
        ]);
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
        assert($webSession->invalidate(), equals($webSession));
        verify($this->sessionStorage, 'clear')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function invalidateInvalidatesSessionId()
    {
        $webSession = $this->createWebSession();
        assert($webSession->invalidate(), equals($webSession));
        verify($this->sessionId, 'invalidate')->wasCalledOnce();
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
        $this->sessionStorage->mapCalls([
                'hasValue' => function($key) use ($value)
                        {
                            if (Session::FINGERPRINT === $key) {
                                return true;
                            }

                            return null !== $value;
                        },
                'value'    => onConsecutiveCalls('aFingerprint', $value)
        ]);
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
        assert(
                $this->createWebSessionWithValues()->value('foo', 'bar'),
                equals('bar')
        );
    }

    /**
     * @test
     */
    public function getValueReturnsStoredValue()
    {
        assert(
                $this->createWebSessionWithValues('baz')->value('foo', 'bar'),
                equals('baz')
        );
    }

    /**
     * @test
     */
    public function getValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        expect(function() { $this->createInvalidWebSession()->value('foo'); })
                ->throws(\LogicException::class);
    }

    /**
     * @test
     */
    public function putValueStoresValue()
    {
        $webSession = $this->createWebSession();
        assert($webSession->putValue('foo', 'bar'), equals($webSession));
        verify($this->sessionStorage, 'putValue')->received('foo', 'bar');
    }

    /**
     * @test
     */
    public function putValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        expect(function() {
                $this->createInvalidWebSession()->putValue('foo', 'bar');
        })->throws(\LogicException::class);
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
        verify($this->sessionStorage, 'removeValue')->received('foo');
    }

    /**
     * @test
     */
    public function removeValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        expect(function() { $this->createInvalidWebSession()->removeValue('foo'); })
                ->throws(\LogicException::class);
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
        assert($session->valueKeys(), equals(['foo']));
    }

    /**
     * @test
     */
    public function getValueKeysThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        expect(function() { $this->createInvalidWebSession()->valueKeys(); })
                ->throws(\LogicException::class);
    }
}

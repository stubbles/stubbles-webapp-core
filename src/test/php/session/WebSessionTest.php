<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\webapp\session\id\SessionId;
use stubbles\webapp\session\storage\SessionStorage;

use function bovigo\assert\{
    assertThat,
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
 */
#[Group('session')]
class WebSessionTest extends TestCase
{
    private SessionStorage&ClassProxy $sessionStorage;
    private SessionId&ClassProxy $sessionId;

    protected function setUp(): void
    {
        $this->sessionStorage = NewInstance::of(SessionStorage::class);
        $this->sessionId      = NewInstance::of(SessionId::class);
    }

    private function createWebSession(
        string $givenFingerprint = 'aFingerprint',
        ?string $storageFingerprint = 'aFingerprint'
    ): WebSession {
        $this->sessionStorage->returns([
            'hasValue' => null !== $storageFingerprint,
            'value'    => $storageFingerprint
        ]);
        return new WebSession(
            $this->sessionStorage,
            $this->sessionId,
            $givenFingerprint
        );
    }

    #[Test]
    public function isNewWhenSessionContainsNoFingerprint(): void
    {
        assertTrue($this->createWebSession('aFingerprint', null)->isNew());
    }

    #[Test]
    public function regeneratesSessionIdWhenSessionIsNew(): void
    {
        $this->createWebSession('aFingerprint', null);
        assertTrue(verify($this->sessionId, 'regenerate')->wasCalledOnce());
    }

    #[Test]
    public function storesFingerPrintWhenSessionIsNew(): void
    {
        $this->createWebSession('aFingerprint', null);
        assertTrue(verify($this->sessionStorage, 'putValue')
                ->received(Session::FINGERPRINT, 'aFingerprint'));
    }

    #[Test]
    public function regeneratesSessionIdWhenSessionIsHijacked(): void
    {
        $this->createWebSession('otherFingerprint');
        assertTrue(verify($this->sessionId, 'regenerate')->wasCalledOnce());
    }

    #[Test]
    public function clearsSessionDataWhenSessionIsHijacked(): void
    {
        $this->createWebSession('otherFingerprint');
        assertTrue(verify($this->sessionStorage, 'clear')->wasCalledOnce());
    }

    #[Test]
    public function storesGivenFingerPrintWhenSessionIsHijacked(): void
    {
        $this->createWebSession('otherFingerprint');
        verify($this->sessionStorage, 'putValue')
            ->received(Session::FINGERPRINT, 'otherFingerprint');
    }

    #[Test]
    public function idIsSessionId(): void
    {
        $this->sessionId->returns(['__toString' => '303']);
        assertThat($this->createWebSession()->id(), equals('303'));
    }

    #[Test]
    public function regenerateCreatesNewSessionId(): void
    {
        $webSession = $this->createWebSession();
        assertThat($webSession->regenerateId(), equals($webSession));
        assertTrue(verify($this->sessionId, 'regenerate')->wasCalledOnce());
    }

    #[Test]
    public function nameIsSessionIdName(): void
    {
        $this->sessionId->returns(['name' => 'foo']);
        assertThat($this->createWebSession()->name(), equals('foo'));
    }

    #[Test]
    public function isValidByDefault(): void
    {
        assertTrue($this->createWebSession()->isValid());
    }

    /**
     * creates invalid web session
     *
     * A web session is invalid if the fingerprint is removed. This is simulated
     * in this case by simply saying that no fingerprint is present on the
     * second request to the storage.
     */
    private function createInvalidWebSession(): WebSession
    {
        $this->sessionStorage->returns([
            'hasValue' => onConsecutiveCalls(true, false),
            'value'    => 'aFingerprint'
        ]);
        return new WebSession(
            $this->sessionStorage,
            $this->sessionId,
            'aFingerprint'
        );
    }
    
    #[Test]
    public function isNotValidWhenFingerprintIsRemoved(): void
    {
        assertFalse($this->createInvalidWebSession()->isValid());
    }

    #[Test]
    public function invalidateClearsSessionData(): void
    {
        $webSession = $this->createWebSession();
        assertThat($webSession->invalidate(), equals($webSession));
        assertTrue(verify($this->sessionStorage, 'clear')->wasCalledOnce());
    }

    #[Test]
    public function invalidateInvalidatesSessionId(): void
    {
        $webSession = $this->createWebSession();
        assertThat($webSession->invalidate(), equals($webSession));
        assertTrue(verify($this->sessionId, 'invalidate')->wasCalledOnce());
    }

    #[Test]
    public function hasValueReturnsFalseOnInvalidSession(): void
    {
        assertFalse($this->createInvalidWebSession()->hasValue('foo'));
    }

    /**
     * creates valid web session with value expectations
     */
    private function createWebSessionWithValues(?string $value = null): WebSession
    {
        $this->sessionStorage->returns([
            'hasValue' =>
                function(string $key) use ($value): bool
                {
                    if (Session::FINGERPRINT === $key) {
                        return true;
                    }

                    return null !== $value;
                },
            'value' => onConsecutiveCalls('aFingerprint', $value)
        ]);
        return new WebSession(
            $this->sessionStorage,
            $this->sessionId,
            'aFingerprint'
        );
    }

    #[Test]
    public function hasNeverAnyValueByDefault(): void
    {
        assertFalse($this->createWebSessionWithValues()->hasValue('foo'));
    }

    #[Test]
    public function hasValueIfStored(): void
    {
        assertTrue($this->createWebSessionWithValues('bar')->hasValue('foo'));
    }

    #[Test]
    public function getValueReturnsNullIfValueNotStored(): void
    {
        assertNull($this->createWebSessionWithValues()->value('foo'));
    }

    #[Test]
    public function getValueReturnsDefaultIfValueNotStoredAndDefaultGiven(): void
    {
        assertThat(
            $this->createWebSessionWithValues()->value('foo', 'bar'),
            equals('bar')
        );
    }

    #[Test]
    public function getValueReturnsStoredValue(): void
    {
        assertThat(
            $this->createWebSessionWithValues('baz')->value('foo', 'bar'),
            equals('baz')
        );
    }

    #[Test]
    public function getValueThrowsIllegalStateExceptionFalseOnInvalidSession(): void
    {
        expect(function() { $this->createInvalidWebSession()->value('foo'); })
            ->throws(\LogicException::class);
    }

    #[Test]
    public function putValueStoresValue(): void
    {
        $webSession = $this->createWebSession();
        $webSession->putValue('foo', 'bar');
        verify($this->sessionStorage, 'putValue')->received('foo', 'bar');
    }

    #[Test]
    public function putValueThrowsIllegalStateExceptionFalseOnInvalidSession(): void
    {
        expect(function() {
            $this->createInvalidWebSession()->putValue('foo', 'bar');
        })->throws(LogicException::class);
    }

    #[Test]
    public function removeReturnsFalseIfValueWasNotStoredBefore(): void
    {
        assertFalse($this->createWebSessionWithValues()->removeValue('foo'));
    }

    #[Test]
    public function removeReturnsTrueIfValueWasNotStoredBefore(): void
    {
        assertTrue($this->createWebSessionWithValues('bar')->removeValue('foo'));
        assertTrue(verify($this->sessionStorage, 'removeValue')->received('foo'));
    }

    #[Test]
    public function removeValueThrowsIllegalStateExceptionFalseOnInvalidSession(): void
    {
        expect(function() { $this->createInvalidWebSession()->removeValue('foo'); })
            ->throws(\LogicException::class);
    }

    #[Test]
    public function getValueKeysReturnsAllKeysWithoutFingerprint(): void
    {
        $session = $this->createWebSession();
        $this->sessionStorage->returns(
            ['hasValue' => true, 'valueKeys' => [Session::FINGERPRINT, 'foo']]
        );
        assertThat($session->valueKeys(), equals(['foo']));
    }

    #[Test]
    public function getValueKeysThrowsIllegalStateExceptionFalseOnInvalidSession(): void
    {
        expect(function() { $this->createInvalidWebSession()->valueKeys(); })
            ->throws(LogicException::class);
    }
}

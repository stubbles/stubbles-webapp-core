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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\webapp\session\id\SessionId;

use function bovigo\assert\assertThat;
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
 */
#[Group('session')]
class NullSessionTest extends TestCase
{
    private NullSession $nullSession;
    private SessionId&ClassProxy $sessionId;

    protected function setUp(): void
    {
        $this->sessionId   = NewInstance::of(SessionId::class);
        $this->nullSession = new NullSession($this->sessionId);
    }

    #[Test]
    public function isAlwaysNew(): void
    {
        assertTrue($this->nullSession->isNew());
    }

    #[Test]
    public function idIsSessionId(): void
    {
        $this->sessionId->returns(['__toString' => '303']);
        assertThat($this->nullSession->id(), equals('303'));
    }

    #[Test]
    public function regenerateCreatesNewSessionId(): void
    {
        assertThat(
                $this->nullSession->regenerateId(),
                equals($this->nullSession)
        );
        verify($this->sessionId, 'regenerate')->wasCalledOnce();
    }

    #[Test]
    public function nameIsSessionIdName(): void
    {
        $this->sessionId->returns(['name' => 'foo']);
        assertThat($this->nullSession->name(), equals('foo'));
    }

    #[Test]
    public function isAlwaysValid(): void
    {
        assertTrue($this->nullSession->isValid());
    }

    #[Test]
    public function invalidateInvalidatesSessionId(): void
    {
        assertThat(
                $this->nullSession->invalidate(),
                equals($this->nullSession)
        );
        verify($this->sessionId, 'invalidate')->wasCalledOnce();
    }

    #[Test]
    public function hasNeverAnyValue(): void
    {
        assertFalse($this->nullSession->hasValue('foo'));
    }

    #[Test]
    public function neverReturnsValue(): void
    {
        assertNull($this->nullSession->value('foo'));
    }

    #[Test]
    public function alwaysReturnsDefaultValue(): void
    {
        assertThat($this->nullSession->value('foo', 'bar'), equals('bar'));
    }

    #[Test]
    public function putValueDoesNothing(): void
    {
        $this->nullSession->putValue('foo', 'bar');
        assertNull($this->nullSession->value('foo'));
    }

    #[Test]
    public function removeAlwaysTellsValueWasNotPresent(): void
    {
        assertFalse($this->nullSession->removeValue('foo'));
    }

    #[Test]
    public function hasNoValueKeys(): void
    {
        assertEmptyArray($this->nullSession->valueKeys());
    }
}

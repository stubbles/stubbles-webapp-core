<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\storage;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\{
    assertThat,
    assertEmptyArray,
    assertFalse,
    assertNotEmpty,
    assertNull,
    assertTrue,
    predicate\equals,
    predicate\isNotEqualTo,
    predicate\isSameAs
};
/**
 * Tests for stubbles\webapp\session\storage\NativeSessionStorage.
 *
 * @since  2.0.0
 */
#[Group('session')]
#[Group('storage')]
class NativeSessionStorageTest extends TestCase
{
    private NativeSessionStorage $nativeSessionStorage;

    protected function setUp(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Testing native session requires no previous output.');
        }

        $this->removeExistingSession();
        $this->nativeSessionStorage = new NativeSessionStorage('foo');
    }

    private function removeExistingSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->removeExistingSession();
    }

    #[Test]
    public function returnsGivenSessionName(): void
    {
        assertThat($this->nativeSessionStorage->name(), equals('foo'));
    }

    #[Test]
    public function returnsIdOfStartedSession(): void
    {
        assertNotEmpty((string) $this->nativeSessionStorage);
    }

    #[Test]
    public function invalidateCreatesNewSessionId(): void
    {
        // order is important, invalidate() changes session id
        $validId   = (string) $this->nativeSessionStorage;
        $invalidId = (string) $this->nativeSessionStorage->invalidate();
        assertThat($invalidId, isNotEqualTo($validId));
    }

    #[Test]
    public function isEmptyAfterClear(): void
    {
        assertEmptyArray(
            $this->nativeSessionStorage->putValue('foo', 'bar')
                ->clear()
                ->valueKeys()
        );
    }

    #[Test]
    public function hasNoValueByDefault(): void
    {
        assertFalse($this->nativeSessionStorage->hasValue('foo'));
    }

    #[Test]
    public function returnsNullForNonExistingValue(): void
    {
        assertNull($this->nativeSessionStorage->value('foo'));
    }

    #[Test]
    public function doesNothingWenRemovingNonExistingValue(): void
    {
        assertThat(
                $this->nativeSessionStorage->removeValue('foo'),
                isSameAs($this->nativeSessionStorage)
        );
    }

    #[Test]
    public function hasValueWhichWasSet(): void
    {
        assertTrue(
            $this->nativeSessionStorage->putValue('foo', 'bar')
                ->hasValue('foo')
        );
    }

    #[Test]
    public function returnsValueWhichWasSet(): void
    {
        assertThat(
            $this->nativeSessionStorage->putValue('foo', 'bar')
                ->value('foo'),
            equals('bar')
        );
    }

    #[Test]
    public function removesExistingValue(): void
    {
        assertFalse(
            $this->nativeSessionStorage->putValue('foo', 'bar')
                ->removeValue('foo')
                ->hasValue('foo')
        );
    }

    #[Test]
    public function hasNoValueKeysByDefault(): void
    {
        assertEmptyArray($this->nativeSessionStorage->valueKeys());
    }

    #[Test]
    public function valueKeysIncludeKeysOfAddedValues(): void
    {
        assertThat(
            $this->nativeSessionStorage->putValue('foo', 'bar')
                ->valueKeys(),
            equals(['foo'])
        );
    }
}

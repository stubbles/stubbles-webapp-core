<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\storage;
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
 * @group  session
 * @group  storage
 */
class NativeSessionStorageTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\session\storage\NativeSessionStorage
     */
    private $nativeSessionStorage;

    protected function setUp(): void
    {
        if (\headers_sent()) {
            $this->markTestSkipped();
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

    /**
     * @test
     */
    public function returnsGivenSessionName(): void
    {
        assertThat($this->nativeSessionStorage->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function returnsIdOfStartedSession(): void
    {
        assertNotEmpty((string) $this->nativeSessionStorage);
    }

    /**
     * @test
     */
    public function invalidateCreatesNewSessionId(): void
    {
        // order is important, invalidate() changes session id
        $validId   = (string) $this->nativeSessionStorage;
        $invalidId = (string) $this->nativeSessionStorage->invalidate();
        assertThat($invalidId, isNotEqualTo($validId));
    }

    /**
     * @test
     */
    public function isEmptyAfterClear(): void
    {
        assertEmptyArray(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->clear()
                        ->valueKeys()
        );
    }

    /**
     * @test
     */
    public function hasNoValueByDefault(): void
    {
        assertFalse($this->nativeSessionStorage->hasValue('foo'));
    }

    /**
     * @test
     */
    public function returnsNullForNonExistingValue(): void
    {
        assertNull($this->nativeSessionStorage->value('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue(): void
    {
        assertThat(
                $this->nativeSessionStorage->removeValue('foo'),
                isSameAs($this->nativeSessionStorage)
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet(): void
    {
        assertTrue(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet(): void
    {
        assertThat(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->value('foo'),
                equals('bar')
        );
    }

    /**
     * @test
     */
    public function removesExistingValue(): void
    {
        assertFalse(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->removeValue('foo')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function hasNoValueKeysByDefault(): void
    {
        assertEmptyArray($this->nativeSessionStorage->valueKeys());
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues(): void
    {
        assertThat(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->valueKeys(),
                equals(['foo'])
        );
    }
}

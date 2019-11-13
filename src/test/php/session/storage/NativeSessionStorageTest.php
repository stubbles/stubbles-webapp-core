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
     * instance to test
     *
     * @type  \stubbles\webapp\session\storage\NativeSessionStorage
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

    private function removeExistingSession()
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
    public function returnsGivenSessionName()
    {
        assertThat($this->nativeSessionStorage->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function returnsIdOfStartedSession()
    {
        assertNotEmpty((string) $this->nativeSessionStorage);
    }

    /**
     * @test
     * @skip  if headers_sent
     */
    public function canRegenerateSessionId()
    {
        $file = null;
        $line = null;
        if (headers_sent($file, $line)) {
            $this->markTestSkipped(
                    'Headers already send in ' . $file . ' on line ' . $line
                    . ', skipped ' . __METHOD__ . '()'
            );
        }

        assertThat(
                (string) $this->nativeSessionStorage->regenerate(),
                isNotEqualTo((string) $this->nativeSessionStorage)
        );
    }

    /**
     * @test
     */
    public function invalidateCreatesNewSessionId()
    {
        // order is important, invalidate() changes session id
        $validId   = (string) $this->nativeSessionStorage;
        $invalidId = (string) $this->nativeSessionStorage->invalidate();
        assertThat($invalidId, isNotEqualTo($validId));
    }

    /**
     * @test
     */
    public function isEmptyAfterClear()
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
    public function hasNoValueByDefault()
    {
        assertFalse($this->nativeSessionStorage->hasValue('foo'));
    }

    /**
     * @test
     */
    public function returnsNullForNonExistingValue()
    {
        assertNull($this->nativeSessionStorage->value('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue()
    {
        assertThat(
                $this->nativeSessionStorage->removeValue('foo'),
                isSameAs($this->nativeSessionStorage)
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet()
    {
        assertTrue(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet()
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
    public function removesExistingValue()
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
    public function hasNoValueKeysByDefault()
    {
        assertEmptyArray($this->nativeSessionStorage->valueKeys());
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues()
    {
        assertThat(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->valueKeys(),
                equals(['foo'])
        );
    }
}

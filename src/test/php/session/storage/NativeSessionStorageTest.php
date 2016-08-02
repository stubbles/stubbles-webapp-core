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
namespace stubbles\webapp\session\storage;
use function bovigo\assert\assert;
use function bovigo\assert\assertEmptyArray;;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNotEmpty;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isNotEqualTo;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\webapp\session\storage\NativeSessionStorage.
 *
 * @since  2.0.0
 * @group  session
 * @group  storage
 */
class NativeSessionStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\session\storage\NativeSessionStorage
     */
    private $nativeSessionStorage;

    /**
     * set up test environment
     */
    public function setUp()
    {
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

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        $this->removeExistingSession();
    }

    /**
     * @test
     */
    public function returnsGivenSessionName()
    {
        assert($this->nativeSessionStorage->name(), equals('foo'));
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

        assert(
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
        assert($invalidId, isNotEqualTo($validId));
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
        assert(
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
        assert(
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
        assert(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->valueKeys(),
                equals(['foo'])
        );
    }
}

<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session\storage;
/**
 * Tests for stubbles\webapp\session\storage\NativeSessionStorage.
 *
 * @since  2.0.0
 * @group  session
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

    /**
     * ensure no session is running
     */
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
        $this->assertEquals('foo', $this->nativeSessionStorage->name());
    }

    /**
     * @test
     */
    public function returnsIdOfStartedSession()
    {
        $this->assertNotEmpty((string) $this->nativeSessionStorage);
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

        $this->assertNotEquals(
                (string) $this->nativeSessionStorage,
                (string) $this->nativeSessionStorage->regenerate()
        );
    }

    /**
     * @test
     */
    public function invalidateCreatesNewSessionId()
    {
        $this->assertNotEquals(
                (string) $this->nativeSessionStorage,
                (string) $this->nativeSessionStorage->invalidate()
        );
    }

    /**
     * @test
     */
    public function isEmptyAfterClear()
    {
        $this->assertEquals(
                [],
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
        $this->assertFalse($this->nativeSessionStorage->hasValue('foo'));
    }

    /**
     * @test
     */
    public function returnsNullForNonExistingValue()
    {
        $this->assertNull($this->nativeSessionStorage->value('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue()
    {
        $this->assertSame(
                $this->nativeSessionStorage,
                $this->nativeSessionStorage->removeValue('foo')
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet()
    {
        $this->assertTrue(
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet()
    {
        $this->assertEquals(
                'bar',
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->value('foo')
        );
    }

    /**
     * @test
     */
    public function removesExistingValue()
    {
        $this->assertFalse(
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
        $this->assertEquals(
                [],
                $this->nativeSessionStorage->valueKeys()
        );
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues()
    {
        $this->assertEquals(
                ['foo'],
                $this->nativeSessionStorage->putValue('foo', 'bar')
                        ->valueKeys()
        );
    }
}

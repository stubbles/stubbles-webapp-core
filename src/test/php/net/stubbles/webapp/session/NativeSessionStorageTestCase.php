<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
/**
 * Tests for net\stubbles\webapp\session\NativeSessionStorage.
 *
 * @since  2.0.0
 * @group  webapp
 * @group  webapp_session
 */
class NativeSessionStorageTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NativeSessionStorage
     */
    private $nativeSessionStorage;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $_SESSION = array();
        $this->nativeSessionStorage = @new NativeSessionStorage('foo');
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        $_SESSION = array();
    }

    /**
     * @test
     */
    public function returnsGivenSessionName()
    {
        $this->assertEquals('foo', $this->nativeSessionStorage->getName());
    }

    /**
     * @test
     */
    public function returnsIdOfStartedSession()
    {
        $this->assertNotEmpty($this->nativeSessionStorage->get());
    }

    /**
     * @test
     */
    public function canRegenerateSessionId()
    {
        $id = $this->nativeSessionStorage->get();
        $file = null;
        $line = null;
        if (headers_sent($file, $line)) {
            $this->markTestSkipped('Headers already send in ' . $file . ' on line ' . $line . ', skipped ' . __METHOD__ . '()');
        }

        $this->assertNotEquals($id,
                               $this->nativeSessionStorage->regenerateId()
                                                          ->get()
        );
    }

    /**
     * @test
     */
    public function invalidateCreatesNewSessionId()
    {
        $id = $this->nativeSessionStorage->get();
        $this->assertNotEquals($id,
                               $this->nativeSessionStorage->invalidate()
                                                          ->get()
        );
    }

    /**
     * @test
     */
    public function isEmptyAfterClear()
    {
        $this->assertEquals(array(),
                            $this->nativeSessionStorage->putValue('foo', 'bar')
                                                       ->clear()
                                                       ->getValueKeys()
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
        $this->assertNull($this->nativeSessionStorage->getValue('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue()
    {
        $this->assertSame($this->nativeSessionStorage,
                          $this->nativeSessionStorage->removeValue('foo')
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet()
    {
        $this->assertTrue($this->nativeSessionStorage->putValue('foo', 'bar')
                                                     ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet()
    {
        $this->assertEquals('bar',
                            $this->nativeSessionStorage->putValue('foo', 'bar')
                                                       ->getValue('foo')
        );
    }

    /**
     * @test
     */
    public function removesExistingValue()
    {
        $this->assertFalse($this->nativeSessionStorage->putValue('foo', 'bar')
                                                      ->removeValue('foo')
                                                      ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function hasNoValueKeysByDefault()
    {
        $this->assertEquals(array(),
                            $this->nativeSessionStorage->getValueKeys()
        );
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues()
    {
        $this->assertEquals(array('foo'),
                            $this->nativeSessionStorage->putValue('foo', 'bar')
                                                       ->getValueKeys()
        );
    }
}
?>
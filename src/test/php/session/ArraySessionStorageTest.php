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
/**
 * Tests for stubbles\webapp\session\ArraySessionStorage.
 *
 * @since  2.0.0
 * @group  session
 */
class ArraySessionStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ArraySessionStorage
     */
    private $arraySessionStorage;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->arraySessionStorage = new ArraySessionStorage();
    }

    /**
     * @test
     */
    public function hasFingerprintByDefault()
    {
        $this->assertTrue($this->arraySessionStorage->hasValue(Session::FINGERPRINT));
    }

    /**
     * @test
     */
    public function fingerprintIsEmptyByDefault()
    {
        $this->assertEquals('', $this->arraySessionStorage->getValue(Session::FINGERPRINT));
    }

    /**
     * @test
     */
    public function isEmptyAfterClear()
    {
        $this->assertEquals([],
                            $this->arraySessionStorage->putValue('foo', 'bar')
                                                      ->clear()
                                                      ->getValueKeys()
        );
    }

    /**
     * @test
     */
    public function hasNoOtherValueByDefault()
    {
        $this->assertFalse($this->arraySessionStorage->hasValue('foo'));
    }

    /**
     * @test
     */
    public function returnsNullForNonExistingValue()
    {
        $this->assertNull($this->arraySessionStorage->getValue('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue()
    {
        $this->assertSame($this->arraySessionStorage,
                          $this->arraySessionStorage->removeValue('foo')
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet()
    {
        $this->assertTrue($this->arraySessionStorage->putValue('foo', 'bar')
                                                    ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet()
    {
        $this->assertEquals('bar',
                            $this->arraySessionStorage->putValue('foo', 'bar')
                                                      ->getValue('foo')
        );
    }

    /**
     * @test
     */
    public function removesExistingValue()
    {
        $this->assertFalse($this->arraySessionStorage->putValue('foo', 'bar')
                                                     ->removeValue('foo')
                                                     ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsDefaultValueKeysAfterCreation()
    {
        $this->assertEquals([Session::FINGERPRINT],
                            $this->arraySessionStorage->getValueKeys()
        );
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues()
    {
        $this->assertEquals([Session::FINGERPRINT,
                                  'foo'
                            ],
                            $this->arraySessionStorage->putValue('foo', 'bar')
                                                      ->getValueKeys()
        );
    }
}

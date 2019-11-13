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
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assertThat,
    assertEmptyArray,
    assertEmptyString,
    assertFalse,
    assertNull,
    assertTrue,
    predicate\equals,
    predicate\isSameAs
};
/**
 * Tests for stubbles\webapp\session\storage\ArraySessionStorage.
 *
 * @since  2.0.0
 * @group  session
 * @group  storage
 */
class ArraySessionStorageTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\session\storage\ArraySessionStorage
     */
    private $arraySessionStorage;

    protected function setUp(): void
    {
        $this->arraySessionStorage = new ArraySessionStorage();
    }

    /**
     * @test
     */
    public function hasFingerprintByDefault()
    {
        assertTrue(
                $this->arraySessionStorage->hasValue(Session::FINGERPRINT)
        );
    }

    /**
     * @test
     */
    public function fingerprintIsEmptyByDefault()
    {
        assertEmptyString($this->arraySessionStorage->value(Session::FINGERPRINT));
    }

    /**
     * @test
     */
    public function isEmptyAfterClear()
    {
        assertEmptyArray(
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->clear()
                        ->valueKeys()
        );
    }

    /**
     * @test
     */
    public function hasNoOtherValueByDefault()
    {
        assertFalse($this->arraySessionStorage->hasValue('foo'));
    }

    /**
     * @test
     */
    public function returnsNullForNonExistingValue()
    {
        assertNull($this->arraySessionStorage->value('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue()
    {
        assertThat(
                $this->arraySessionStorage->removeValue('foo'),
                isSameAs($this->arraySessionStorage)
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet()
    {
        assertTrue(
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet()
    {
        assertThat(
                $this->arraySessionStorage->putValue('foo', 'bar')
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
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->removeValue('foo')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsDefaultValueKeysAfterCreation()
    {
        assertThat(
                $this->arraySessionStorage->valueKeys(),
                equals([Session::FINGERPRINT])
        );
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues()
    {
        assertThat(
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->valueKeys(),
                equals([Session::FINGERPRINT, 'foo'])
        );
    }
}

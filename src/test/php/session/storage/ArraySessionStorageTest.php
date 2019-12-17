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
     * @var  \stubbles\webapp\session\storage\ArraySessionStorage
     */
    private $arraySessionStorage;

    protected function setUp(): void
    {
        $this->arraySessionStorage = new ArraySessionStorage();
    }

    /**
     * @test
     */
    public function hasFingerprintByDefault(): void
    {
        assertTrue(
                $this->arraySessionStorage->hasValue(Session::FINGERPRINT)
        );
    }

    /**
     * @test
     */
    public function fingerprintIsEmptyByDefault(): void
    {
        assertEmptyString($this->arraySessionStorage->value(Session::FINGERPRINT));
    }

    /**
     * @test
     */
    public function isEmptyAfterClear(): void
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
    public function hasNoOtherValueByDefault(): void
    {
        assertFalse($this->arraySessionStorage->hasValue('foo'));
    }

    /**
     * @test
     */
    public function returnsNullForNonExistingValue(): void
    {
        assertNull($this->arraySessionStorage->value('foo'));
    }

    /**
     * @test
     */
    public function doesNothingWenRemovingNonExistingValue(): void
    {
        assertThat(
                $this->arraySessionStorage->removeValue('foo'),
                isSameAs($this->arraySessionStorage)
        );
    }

    /**
     * @test
     */
    public function hasValueWhichWasSet(): void
    {
        assertTrue(
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->hasValue('foo')
        );
    }

    /**
     * @test
     */
    public function returnsValueWhichWasSet(): void
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
    public function removesExistingValue(): void
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
    public function returnsDefaultValueKeysAfterCreation(): void
    {
        assertThat(
                $this->arraySessionStorage->valueKeys(),
                equals([Session::FINGERPRINT])
        );
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues(): void
    {
        assertThat(
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->valueKeys(),
                equals([Session::FINGERPRINT, 'foo'])
        );
    }
}

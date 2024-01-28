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
 */
#[Group('session')]
#[Group('storage')]
class ArraySessionStorageTest extends TestCase
{
    private ArraySessionStorage $arraySessionStorage;

    protected function setUp(): void
    {
        $this->arraySessionStorage = new ArraySessionStorage();
    }

    #[Test]
    public function hasFingerprintByDefault(): void
    {
        assertTrue(
            $this->arraySessionStorage->hasValue(Session::FINGERPRINT)
        );
    }

    #[Test]
    public function fingerprintIsEmptyByDefault(): void
    {
        assertEmptyString($this->arraySessionStorage->value(Session::FINGERPRINT));
    }

    #[Test]
    public function isEmptyAfterClear(): void
    {
        assertEmptyArray(
            $this->arraySessionStorage->putValue('foo', 'bar')
                ->clear()
                ->valueKeys()
        );
    }

    #[Test]
    public function hasNoOtherValueByDefault(): void
    {
        assertFalse($this->arraySessionStorage->hasValue('foo'));
    }

    #[Test]
    public function returnsNullForNonExistingValue(): void
    {
        assertNull($this->arraySessionStorage->value('foo'));
    }

    #[Test]
    public function doesNothingWenRemovingNonExistingValue(): void
    {
        assertThat(
            $this->arraySessionStorage->removeValue('foo'),
            isSameAs($this->arraySessionStorage)
        );
    }

    #[Test]
    public function hasValueWhichWasSet(): void
    {
        assertTrue(
            $this->arraySessionStorage->putValue('foo', 'bar')
                ->hasValue('foo')
        );
    }

    #[Test]
    public function returnsValueWhichWasSet(): void
    {
        assertThat(
            $this->arraySessionStorage->putValue('foo', 'bar')
                ->value('foo'),
            equals('bar')
        );
    }

    #[Test]
    public function removesExistingValue(): void
    {
        assertFalse(
            $this->arraySessionStorage->putValue('foo', 'bar')
                ->removeValue('foo')
                ->hasValue('foo')
        );
    }

    #[Test]
    public function returnsDefaultValueKeysAfterCreation(): void
    {
        assertThat(
            $this->arraySessionStorage->valueKeys(),
            equals([Session::FINGERPRINT])
        );
    }

    #[Test]
    public function valueKeysIncludeKeysOfAddedValues(): void
    {
        assertThat(
            $this->arraySessionStorage->putValue('foo', 'bar')
                ->valueKeys(),
            equals([Session::FINGERPRINT, 'foo'])
        );
    }
}

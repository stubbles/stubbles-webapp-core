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
use stubbles\webapp\session\Session;

use function bovigo\assert\assert;
use function bovigo\assert\assertEmptyArray;
use function bovigo\assert\assertEmptyString;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\webapp\session\storage\ArraySessionStorage.
 *
 * @since  2.0.0
 * @group  session
 * @group  storage
 */
class ArraySessionStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\session\storage\ArraySessionStorage
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
        assert(
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
        assert(
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
        assert(
                $this->arraySessionStorage->valueKeys(),
                equals([Session::FINGERPRINT])
        );
    }

    /**
     * @test
     */
    public function valueKeysIncludeKeysOfAddedValues()
    {
        assert(
                $this->arraySessionStorage->putValue('foo', 'bar')
                        ->valueKeys(),
                equals([Session::FINGERPRINT, 'foo'])
        );
    }
}

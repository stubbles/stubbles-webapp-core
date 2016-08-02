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
namespace stubbles\webapp\session\id;
use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isNotEqualTo;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\assert\predicate\matches;
/**
 * Tests for stubbles\webapp\session\id\NoneDurableSessionId.
 *
 * @since  2.0.0
 * @group  session
 * @group  id
 */
class NoneDurableSessionIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NoneDurableSessionId
     */
    private $noneDurableSessionId;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->noneDurableSessionId = new NoneDurableSessionId();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function sessionNameStaysSameForInstance()
    {
        assert(
                $this->noneDurableSessionId->name(),
                equals($this->noneDurableSessionId->name())
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function sessionNameIsDifferentForDifferentInstances()
    {
        $other = new NoneDurableSessionId();
        assert(
                $this->noneDurableSessionId->name(),
                isNotEqualTo($other->name())
        );
    }

    /**
     * @test
     */
    public function hasSessionId()
    {
        assert(
                (string) $this->noneDurableSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId()
    {
        $previous = (string) $this->noneDurableSessionId;
        assert(
                (string) $this->noneDurableSessionId->regenerate(),
                isNotEqualTo($previous)
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid()
    {
        assert(
                (string) $this->noneDurableSessionId->regenerate(),
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function invalidateDoesNothing()
    {
        assert(
                $this->noneDurableSessionId->invalidate(),
                isSameAs($this->noneDurableSessionId)
        );
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionNameWhenProvided()
    {
        assert((new NoneDurableSessionId('foo'))->name(), equals('foo'));
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionIdWhenProvided()
    {
        assert((string) new NoneDurableSessionId('foo', '313'), equals('313'));
    }
}

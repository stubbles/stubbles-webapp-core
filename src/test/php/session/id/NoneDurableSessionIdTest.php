<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\id;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
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
class NoneDurableSessionIdTest extends TestCase
{
    /**
     * @var  NoneDurableSessionId
     */
    private $noneDurableSessionId;

    protected function setUp(): void
    {
        $this->noneDurableSessionId = new NoneDurableSessionId();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function sessionNameStaysSameForInstance(): void
    {
        assertThat(
                $this->noneDurableSessionId->name(),
                equals($this->noneDurableSessionId->name())
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function sessionNameIsDifferentForDifferentInstances(): void
    {
        $other = new NoneDurableSessionId();
        assertThat(
                $this->noneDurableSessionId->name(),
                isNotEqualTo($other->name())
        );
    }

    /**
     * @test
     */
    public function hasSessionId(): void
    {
        assertThat(
                (string) $this->noneDurableSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId(): void
    {
        $previous = (string) $this->noneDurableSessionId;
        assertThat(
                (string) $this->noneDurableSessionId->regenerate(),
                isNotEqualTo($previous)
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid(): void
    {
        assertThat(
                (string) $this->noneDurableSessionId->regenerate(),
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function invalidateDoesNothing(): void
    {
        assertThat(
                $this->noneDurableSessionId->invalidate(),
                isSameAs($this->noneDurableSessionId)
        );
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionNameWhenProvided(): void
    {
        assertThat((new NoneDurableSessionId('foo'))->name(), equals('foo'));
    }

    /**
     * @test
     * @since  5.0.1
     */
    public function hasGivenSessionIdWhenProvided(): void
    {
        assertThat((string) new NoneDurableSessionId('foo', '313'), equals('313'));
    }
}

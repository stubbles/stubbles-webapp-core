<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\id;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('session')]
#[Group('id')]
class NoneDurableSessionIdTest extends TestCase
{
    private NoneDurableSessionId $noneDurableSessionId;

    protected function setUp(): void
    {
        $this->noneDurableSessionId = new NoneDurableSessionId();
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function sessionNameStaysSameForInstance(): void
    {
        assertThat(
            $this->noneDurableSessionId->name(),
            equals($this->noneDurableSessionId->name())
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function sessionNameIsDifferentForDifferentInstances(): void
    {
        $other = new NoneDurableSessionId();
        assertThat(
            $this->noneDurableSessionId->name(),
            isNotEqualTo($other->name())
        );
    }

    #[Test]
    public function hasSessionId(): void
    {
        assertThat(
            (string) $this->noneDurableSessionId,
            matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    #[Test]
    public function regenerateChangesSessionId(): void
    {
        $previous = (string) $this->noneDurableSessionId;
        assertThat(
            (string) $this->noneDurableSessionId->regenerate(),
            isNotEqualTo($previous)
        );
    }

    #[Test]
    public function regeneratedSessionIdIsValid(): void
    {
        assertThat(
            (string) $this->noneDurableSessionId->regenerate(),
            matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    #[Test]
    public function invalidateDoesNothing(): void
    {
        assertThat(
            $this->noneDurableSessionId->invalidate(),
            isSameAs($this->noneDurableSessionId)
        );
    }

    /**
     * @since  5.0.1
     */
    #[Test]
    public function hasGivenSessionNameWhenProvided(): void
    {
        assertThat((new NoneDurableSessionId('foo'))->name(), equals('foo'));
    }

    /**
     * @since  5.0.1
     */
    #[Test]
    public function hasGivenSessionIdWhenProvided(): void
    {
        assertThat((string) new NoneDurableSessionId('foo', '313'), equals('313'));
    }
}

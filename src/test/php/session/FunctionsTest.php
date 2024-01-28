<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\isInstanceOf;
/**
 * Tests for stubbles\webapp\session\*().
 *
 * @since  4.0.0
 */
#[Group('session')]
class FunctionsTest extends TestCase
{
    #[Test]
    public function nativeCreatesWebSession(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Testing native session requires no previous output.');
        }

        assertThat(
            native('example', md5('example user agent')),
            isInstanceOf(WebSession::class)
        );
    }

    #[Test]
    public function noneDurableCreatesWebSession(): void
    {
        assertThat(noneDurable(), isInstanceOf(WebSession::class));
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    public function nullSessionCreatesNullSession(): void
    {
        assertThat(nullSession(), isInstanceOf(NullSession::class));
    }
}

<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\isInstanceOf;
/**
 * Tests for stubbles\webapp\session\*().
 *
 * @since  4.0.0
 * @group  session
 */
class FunctionsTest extends TestCase
{
    /**
     * @test
     */
    public function nativeCreatesWebSession(): void
    {
        if (\headers_sent()) {
            $this->markTestSkipped();
        }

        assertThat(
                native('example', md5('example user agent')),
                isInstanceOf(WebSession::class)
        );
    }

    /**
     * @test
     */
    public function noneDurableCreatesWebSession(): void
    {
        assertThat(noneDurable(), isInstanceOf(WebSession::class));
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function nullSessionCreatesNullSession(): void
    {
        assertThat(nullSession(), isInstanceOf(NullSession::class));
    }
}

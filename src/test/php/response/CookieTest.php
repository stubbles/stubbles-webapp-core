<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertNull,
    assertTrue,
    predicate\equals,
    predicate\isGreaterThanOrEqualTo
};
/**
 * Tests for stubbles\webapp\response\Cookie.
 *
 * @group  response
 */
class CookieTest extends TestCase
{
    /**
     * @test
     */
    public function returnsGivenName(): void
    {
        assertThat(Cookie::create('foo', 'bar')->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function returnsGivenValue(): void
    {
        assertThat(Cookie::create('foo', 'bar')->value(), equals('bar'));
    }

    /**
     * @test
     */
    public function hasNoExpirationDateByDefault(): void
    {
        assertThat(Cookie::create('foo', 'bar')->expiration(), equals(0));
    }

    /**
     * @test
     */
    public function hasNoPathByDefault(): void
    {
        assertNull(Cookie::create('foo', 'bar')->path());
    }

    /**
     * @test
     */
    public function hasNoDomainByDefault(): void
    {
        assertNull(Cookie::create('foo', 'bar')->domain());
    }

    /**
     * @test
     */
    public function isNotRestrictedToSslByDefault(): void
    {
        assertFalse(Cookie::create('foo', 'bar')->isRestrictedToSsl());
    }

    /**
     * @test
     */
    public function isHttpOnlyByDefault(): void
    {
        assertTrue(Cookie::create('foo', 'bar')->isHttpOnly());
    }

    /**
     * @test
     */
    public function expiresAtUsesGivenTimestamp(): void
    {
        $expires = time() + 100; // expire after 100 seconds
        assertThat(
                Cookie::create('foo', 'bar')->expiringAt($expires)->expiration(),
                equals($expires)
        );
    }

    /**
     * @test
     * @group  bug255
     */
    public function expiresInAddsCurrentTime(): void
    {
        assertThat(
                Cookie::create('foo', 'bar')->expiringIn(100)->expiration(),
                isGreaterThanOrEqualTo(time() + 100)
        );
    }

    /**
     * @test
     */
    public function usesGivenPath(): void
    {
        assertThat(
                Cookie::create('foo', 'bar')->forPath('bar')->path(),
                equals('bar')
        );
    }

    /**
     * @test
     */
    public function usesGivenDomain(): void
    {
        assertThat(
                Cookie::create('foo', 'bar')->forDomain('.example.org')->domain(),
                equals('.example.org')
        );
    }

    /**
     * @test
     */
    public function isRestrictedToSslIfEnabled(): void
    {
        assertTrue(
                Cookie::create('foo', 'bar')->restrictToSsl()->isRestrictedToSsl()
         );
    }

    /**
     * @test
     */
    public function httpOnlyCanBeDisabled(): void
    {
        assertFalse(
                Cookie::create('foo', 'bar')->disableHttpOnly()->isHttpOnly()
         );
    }
}

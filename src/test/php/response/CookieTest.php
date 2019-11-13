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
    public function returnsGivenName()
    {
        assertThat(Cookie::create('foo', 'bar')->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function returnsGivenValue()
    {
        assertThat(Cookie::create('foo', 'bar')->value(), equals('bar'));
    }

    /**
     * @test
     */
    public function hasNoExpirationDateByDefault()
    {
        assertThat(Cookie::create('foo', 'bar')->expiration(), equals(0));
    }

    /**
     * @test
     */
    public function hasNoPathByDefault()
    {
        assertNull(Cookie::create('foo', 'bar')->path());
    }

    /**
     * @test
     */
    public function hasNoDomainByDefault()
    {
        assertNull(Cookie::create('foo', 'bar')->domain());
    }

    /**
     * @test
     */
    public function isNotRestrictedToSslByDefault()
    {
        assertFalse(Cookie::create('foo', 'bar')->isRestrictedToSsl());
    }

    /**
     * @test
     */
    public function isHttpOnlyByDefault()
    {
        assertTrue(Cookie::create('foo', 'bar')->isHttpOnly());
    }

    /**
     * @test
     */
    public function expiresAtUsesGivenTimestamp()
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
    public function expiresInAddsCurrentTime()
    {
        assertThat(
                Cookie::create('foo', 'bar')->expiringIn(100)->expiration(),
                isGreaterThanOrEqualTo(time() + 100)
        );
    }

    /**
     * @test
     */
    public function usesGivenPath()
    {
        assertThat(
                Cookie::create('foo', 'bar')->forPath('bar')->path(),
                equals('bar')
        );
    }

    /**
     * @test
     */
    public function usesGivenDomain()
    {
        assertThat(
                Cookie::create('foo', 'bar')->forDomain('.example.org')->domain(),
                equals('.example.org')
        );
    }

    /**
     * @test
     */
    public function isRestrictedToSslIfEnabled()
    {
        assertTrue(
                Cookie::create('foo', 'bar')->restrictToSsl()->isRestrictedToSsl()
         );
    }

    /**
     * @test
     */
    public function httpOnlyCanBeDisabled()
    {
        assertFalse(
                Cookie::create('foo', 'bar')->disableHttpOnly()->isHttpOnly()
         );
    }
}

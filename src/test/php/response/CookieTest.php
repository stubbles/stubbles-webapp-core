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
namespace stubbles\webapp\response;
use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isGreaterThanOrEqualTo;
/**
 * Tests for stubbles\webapp\response\Cookie.
 *
 * @group  response
 */
class CookieTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function returnsGivenName()
    {
        assert(Cookie::create('foo', 'bar')->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function returnsGivenValue()
    {
        assert(Cookie::create('foo', 'bar')->value(), equals('bar'));
    }

    /**
     * @test
     */
    public function hasNoExpirationDateByDefault()
    {
        assert(Cookie::create('foo', 'bar')->expiration(), equals(0));
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
        assert(
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
        assert(
                Cookie::create('foo', 'bar')->expiringIn(100)->expiration(),
                isGreaterThanOrEqualTo(time() + 100)
        );
    }

    /**
     * @test
     */
    public function usesGivenPath()
    {
        assert(
                Cookie::create('foo', 'bar')->forPath('bar')->path(),
                equals('bar')
        );
    }

    /**
     * @test
     */
    public function usesGivenDomain()
    {
        assert(
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

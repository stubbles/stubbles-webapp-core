<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
/**
 * Tests for stubbles\webapp\response\CacheControl.
 *
 * @group  response
 * @group  issue_71
 * @since  5.1.0
 */
class CacheControlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\response\CacheControl
     */
    private $cacheControl;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->cacheControl = new CacheControl();
    }

    /**
     * @test
     */
    public function onlyPrivateEnabledByDefault()
    {
        assertEquals('private', $this->cacheControl);
    }

    /**
     * @test
     */
    public function enablePublicDisablesPrivate()
    {
        assertEquals('public', $this->cacheControl->enablePublic());
    }

    /**
     * @test
     */
    public function mustRevalidateEnabled()
    {
        assertEquals(
                'must-revalidate',
                $this->cacheControl->disablePrivate()->mustRevalidate()
        );
    }

    /**
     * @test
     */
    public function proxyRevalidateEnabled()
    {
        assertEquals(
                'proxy-revalidate',
                $this->cacheControl->disablePrivate()->proxyRevalidate()
        );
    }

    /**
     * @test
     */
    public function noCacheEnabled()
    {
        assertEquals(
                'no-cache',
                $this->cacheControl->disablePrivate()->noCache()
        );
    }

    /**
     * @test
     */
    public function noStoreEnabled()
    {
        assertEquals(
                'no-store',
                $this->cacheControl->disablePrivate()->noStore()
        );
    }

    /**
     * @test
     */
    public function noTransformEnabled()
    {
        assertEquals(
                'no-transform',
                $this->cacheControl->disablePrivate()->noTransform()
        );
    }

    /**
     * @test
     */
    public function maxAgeSet()
    {
        assertEquals(
                'max-age=3',
                $this->cacheControl->disablePrivate()->maxAge(3)
        );
    }

    /**
     * @test
     */
    public function sMaxAgeSet()
    {
        assertEquals(
                's-maxage=3',
                $this->cacheControl->disablePrivate()->sMaxAge(3)
        );
    }

    /**
     * @test
     */
    public function severalDirectives()
    {
        assertEquals(
                'must-revalidate, no-cache, no-store, private',
                $this->cacheControl->mustRevalidate()->noCache()->noStore()
        );
    }
}

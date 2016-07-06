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
use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
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
        assert($this->cacheControl, equals('private'));
    }

    /**
     * @test
     */
    public function enablePublicDisablesPrivate()
    {
        assert($this->cacheControl->enablePublic(), equals('public'));
    }

    /**
     * @test
     */
    public function mustRevalidateEnabled()
    {
        assert(
                $this->cacheControl->disablePrivate()->mustRevalidate(),
                equals('must-revalidate')
        );
    }

    /**
     * @test
     */
    public function proxyRevalidateEnabled()
    {
        assert(
                $this->cacheControl->disablePrivate()->proxyRevalidate(),
                equals('proxy-revalidate')
        );
    }

    /**
     * @test
     */
    public function noCacheEnabled()
    {
        assert(
                $this->cacheControl->disablePrivate()->noCache(),
                equals('no-cache')
        );
    }

    /**
     * @test
     */
    public function noStoreEnabled()
    {
        assert(
                $this->cacheControl->disablePrivate()->noStore(),
                equals('no-store')
        );
    }

    /**
     * @test
     */
    public function noTransformEnabled()
    {
        assert(
                $this->cacheControl->disablePrivate()->noTransform(),
                equals('no-transform')
        );
    }

    /**
     * @test
     */
    public function maxAgeSet()
    {
        assert(
                $this->cacheControl->disablePrivate()->maxAge(3),
                equals('max-age=3')
        );
    }

    /**
     * @test
     */
    public function sMaxAgeSet()
    {
        assert(
                $this->cacheControl->disablePrivate()->sMaxAge(3),
                equals('s-maxage=3')
        );
    }

    /**
     * @test
     */
    public function severalDirectives()
    {
        assert(
                $this->cacheControl->mustRevalidate()->noCache()->noStore(),
                equals('must-revalidate, no-cache, no-store, private')
        );
    }
}

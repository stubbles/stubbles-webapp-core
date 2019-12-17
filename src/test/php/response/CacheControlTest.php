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

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\CacheControl.
 *
 * @group  response
 * @group  issue_71
 * @since  5.1.0
 */
class CacheControlTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\response\CacheControl
     */
    private $cacheControl;

    protected function setUp(): void
    {
        $this->cacheControl = new CacheControl();
    }

    /**
     * @test
     */
    public function onlyPrivateEnabledByDefault(): void
    {
        assertThat($this->cacheControl, equals('private'));
    }

    /**
     * @test
     */
    public function enablePublicDisablesPrivate(): void
    {
        assertThat($this->cacheControl->enablePublic(), equals('public'));
    }

    /**
     * @test
     */
    public function mustRevalidateEnabled(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->mustRevalidate(),
                equals('must-revalidate')
        );
    }

    /**
     * @test
     */
    public function proxyRevalidateEnabled(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->proxyRevalidate(),
                equals('proxy-revalidate')
        );
    }

    /**
     * @test
     */
    public function noCacheEnabled(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->noCache(),
                equals('no-cache')
        );
    }

    /**
     * @test
     */
    public function noStoreEnabled(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->noStore(),
                equals('no-store')
        );
    }

    /**
     * @test
     */
    public function noTransformEnabled(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->noTransform(),
                equals('no-transform')
        );
    }

    /**
     * @test
     */
    public function maxAgeSet(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->maxAge(3),
                equals('max-age=3')
        );
    }

    /**
     * @test
     */
    public function sMaxAgeSet(): void
    {
        assertThat(
                $this->cacheControl->disablePrivate()->sMaxAge(3),
                equals('s-maxage=3')
        );
    }

    /**
     * @test
     */
    public function severalDirectives(): void
    {
        assertThat(
                $this->cacheControl->mustRevalidate()->noCache()->noStore(),
                equals('must-revalidate, no-cache, no-store, private')
        );
    }
}

<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\response\CacheControl.
 *
 * @since  5.1.0
 */
#[Group('response')]
#[Group('issue_71')]
class CacheControlTest extends TestCase
{
    private CacheControl $cacheControl;

    protected function setUp(): void
    {
        $this->cacheControl = new CacheControl();
    }

    #[Test]
    public function onlyPrivateEnabledByDefault(): void
    {
        assertThat($this->cacheControl, equals('private'));
    }

    #[Test]
    public function enablePublicDisablesPrivate(): void
    {
        assertThat($this->cacheControl->enablePublic(), equals('public'));
    }

    #[Test]
    public function mustRevalidateEnabled(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->mustRevalidate(),
            equals('must-revalidate')
        );
    }

    #[Test]
    public function proxyRevalidateEnabled(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->proxyRevalidate(),
            equals('proxy-revalidate')
        );
    }

    #[Test]
    public function noCacheEnabled(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->noCache(),
            equals('no-cache')
        );
    }

    #[Test]
    public function noStoreEnabled(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->noStore(),
            equals('no-store')
        );
    }

    #[Test]
    public function noTransformEnabled(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->noTransform(),
            equals('no-transform')
        );
    }

    #[Test]
    public function maxAgeSet(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->maxAge(3),
            equals('max-age=3')
        );
    }

    #[Test]
    public function sMaxAgeSet(): void
    {
        assertThat(
            $this->cacheControl->disablePrivate()->sMaxAge(3),
            equals('s-maxage=3')
        );
    }

    #[Test]
    public function severalDirectives(): void
    {
        assertThat(
            $this->cacheControl->mustRevalidate()->noCache()->noStore(),
            equals('must-revalidate, no-cache, no-store, private')
        );
    }
}

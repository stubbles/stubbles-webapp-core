<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpVersion;
use stubbles\webapp\Request;
use stubbles\webapp\response\{Error, WebResponse};

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\routing\NotFound.
 *
 * @since  2.2.0
 */
#[Group('routing')]
class NotFoundTest extends TestCase
{
    private NotFound $notFound;

    protected function setUp(): void
    {
        $this->notFound = new NotFound(
            NewInstance::stub(Injector::class),
            new CalledUri('http://example.com/hello/world', 'GET'),
            NewInstance::stub(Interceptors::class),
            new SupportedMimeTypes([])
        );
    }

    #[Test]
    public function doesNotRequireSwitchToHttps(): void
    {
        assertFalse($this->notFound->requiresHttps());
    }

    #[Test]
    public function returns404NotFoundError(): void
    {
        $request = NewInstance::of(Request::class)->returns([
            'protocolVersion' => new HttpVersion(1, 1)
        ]);
        $response = new WebResponse($request);
        assertThat(
            $this->notFound->resolve($request, $response),
            equals(Error::notFound())
        );
    }

    #[Test]
    public function sets404NotFoundStatusCode(): void
    {
        $request = NewInstance::of(Request::class)->returns([
            'protocolVersion' => new HttpVersion(1, 1)
        ]);
        $response = new WebResponse($request);
        $this->notFound->resolve($request, $response);
        assertThat($response->statusCode(), equals(404));
    }
}

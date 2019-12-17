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
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Injector;
use stubbles\peer\http\{Http, HttpVersion};
use stubbles\webapp\Request;
use stubbles\webapp\response\{Error, WebResponse};

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\routing\MethodNotAllowed.
 *
 * @since  2.2.0
 * @group  routing
 */
class MethodNotAllowedTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\routing\MethodNotAllowed
     */
    private $methodNotAllowed;

    protected function setUp(): void
    {
        $this->methodNotAllowed = new MethodNotAllowed(
                NewInstance::stub(Injector::class),
                new CalledUri('http://example.com/hello/world', Http::GET),
                NewInstance::stub(Interceptors::class),
                new SupportedMimeTypes([]),
                [Http::GET, Http::POST, Http::HEAD]
        );
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps(): void
    {
        assertFalse($this->methodNotAllowed->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsMethodNotAllowedError(): void
    {
        $request = NewInstance::of(Request::class)->returns([
                'method'          => Http::DELETE,
                'protocolVersion' => new HttpVersion(1, 1)
        ]);
        $response = new WebResponse($request);
        assertThat(
                $this->methodNotAllowed->resolve($request, $response),
                equals(Error::methodNotAllowed(
                        Http::DELETE,
                        [Http::GET, Http::POST, Http::HEAD, Http::OPTIONS]
                ))
        );
    }

    /**
     * @test
     */
    public function sets405MethodNotAllowedStatusCode(): void
    {
        $request = NewInstance::of(Request::class)->returns([
                'method'          => Http::DELETE,
                'protocolVersion' => new HttpVersion(1, 1)
        ]);
        $response = new WebResponse($request);
        $this->methodNotAllowed->resolve($request, $response);
        assertThat($response->statusCode(), equals(405));
    }
}

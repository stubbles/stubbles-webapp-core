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
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use stubbles\ioc\Injector;
use stubbles\peer\http\{Http, HttpVersion};
use stubbles\webapp\Request;
use stubbles\webapp\response\{Error, WebResponse};

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\predicate\equals;
/**
 * Tests for stubbles\webapp\routing\MethodNotAllowed.
 *
 * @since  2.2.0
 * @group  routing
 */
class MethodNotAllowedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\MethodNotAllowed
     */
    private $methodNotAllowed;

    /**
     * set up test environment
     */
    public function setUp()
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
    public function doesNotRequireSwitchToHttps()
    {
        assertFalse($this->methodNotAllowed->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsMethodNotAllowedError()
    {
        $request = NewInstance::of(Request::class)->mapCalls([
                'method'          => Http::DELETE,
                'protocolVersion' => new HttpVersion(1, 1)
        ]);
        $response = new WebResponse($request);
        assert(
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
    public function sets405MethodNotAllowedStatusCode()
    {
        $request = NewInstance::of(Request::class)->mapCalls([
                'method'          => Http::DELETE,
                'protocolVersion' => new HttpVersion(1, 1)
        ]);
        $response = new WebResponse($request);
        $this->methodNotAllowed->resolve($request, $response);
        assert($response->statusCode(), equals(405));
    }
}

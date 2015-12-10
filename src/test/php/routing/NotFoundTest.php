<?php
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
use stubbles\peer\http\HttpVersion;
use stubbles\webapp\Request;
use stubbles\webapp\response\Error;
use stubbles\webapp\response\WebResponse;
use stubbles\webapp\routing\Interceptors;
/**
 * Tests for stubbles\webapp\routing\NotFound.
 *
 * @since  2.2.0
 * @group  routing
 */
class NotFoundTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\NotFound
     */
    private $notFound;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->notFound = new NotFound(
                NewInstance::stub(Injector::class),
                new CalledUri('http://example.com/hello/world', 'GET'),
                NewInstance::stub(Interceptors::class),
                new SupportedMimeTypes([])
        );
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        assertFalse($this->notFound->requiresHttps());
    }

    /**
     * @test
     */
    public function returns404NotFoundError()
    {
        $request = NewInstance::of(Request::class)->mapCalls(
                ['protocolVersion' => new HttpVersion(1, 1)]
        );
        $response = new WebResponse($request);
        assertEquals(
                Error::notFound(),
                $this->notFound->resolve($request, $response)
        );
    }

    /**
     * @test
     */
    public function sets404NotFoundStatusCode()
    {
        $request = NewInstance::of(Request::class)->mapCalls(
                ['protocolVersion' => new HttpVersion(1, 1)]
        );
        $response = new WebResponse($request);
        $this->notFound->resolve($request, $response);
        assertEquals(404, $response->statusCode());
    }
}
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
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\response\Error;
/**
 * Tests for stubbles\webapp\routing\ResolvingResource.
 *
 * @since  2.0.0
 * @group  routing
 */
class ResolvingResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;
    /**
     * mocked injector instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse = $this->getMock('stubbles\webapp\Response');
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                                   ->disableOriginalConstructor()
                                   ->getMock();
    }

    /**
     * creates instance to test
     *
     * @param   \stubbles\webapp\routing\Route  $routeConfig
     * @return  \stubbles\webapp\routing\ResolvingResource
     */
    private function createMatchingRoute(Route $routeConfig, $uri = 'http://example.com/hello/world')
    {
        return new ResolvingResource(
                $this->mockInjector,
                new CalledUri($uri, 'GET'),
                $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new SupportedMimeTypes([]),
                $routeConfig
        );
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsIfCalledUriIsNotHttpsButRouteRequiresHttps()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $processableRoute = $this->createMatchingRoute($route->httpsOnly());
        $this->assertTrue($processableRoute->requiresHttps());
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsNotHttpsAndRouteDoesNotRequireHttps()
    {
        $this->assertFalse($this->createMatchingRouteWithCallback(function() {})->requiresHttps());
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsHttps()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $processableRoute = $this->createMatchingRoute($route->httpsOnly(), 'https://example.com/hello/world');
        $this->assertFalse($processableRoute->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals('https://example.com/hello/world',
                            (string) $this->createMatchingRouteWithCallback(function() {})->httpsUri()
        );
    }

    /**
     * creates instance to test
     *
     * @param   callable  $callback
     * @return  Resource
     */
    private function createMatchingRouteWithCallback($callback)
    {
        return $this->createMatchingRoute(
                new Route('/hello/{name}', $callback, 'GET')
        );
    }

    /**
     * @test
     */
    public function processCallsClosureGivenAsCallback()
    {
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418))
                           ->will($this->returnSelf());
        $this->assertEquals(
                'Hello world',
                $this->createMatchingRouteWithCallback(
                        function(Request $request, Response $response, UriPath $uriPath)
                        {
                            $response->setStatusCode(418);
                            return 'Hello world';
                        }
                )->data($this->mockRequest, $this->mockResponse)
        );
    }

    /**
     * helper method for the test
     *
     * @param  \stubbles\webapp\Request   $request
     * @param  \stubbles\webapp\Response  $response
     */
    public function theCallable(Request $request, Response $response, UriPath $uriPath)
    {
        $response->setStatusCode(418);
        return 'Hello world';
    }

    /**
     * @test
     */
    public function processCallsGivenCallback()
    {
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418))
                           ->will($this->returnSelf());
        $this->assertEquals(
                'Hello world',
                $this->createMatchingRouteWithCallback([$this, 'theCallable'])
                        ->data($this->mockRequest, $this->mockResponse)
        );
    }

    /**
     * helper method for the test
     *
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\respone\Response  $response
     * @throws  \Exception
     */
    public function failingCallable(Request $request, Response $response, UriPath $uriPath)
    {
        throw new \Exception('some error occurred');
    }

    /**
     * @test
     */
    public function processCallsGivenProcessorInstance()
    {
        $mockProcessor = $this->getMock('stubbles\webapp\Processor');
        $mockProcessor->expects($this->once())
                ->method('process')
                ->with($this->equalTo($this->mockRequest),
                       $this->equalTo($this->mockResponse),
                       $this->equalTo(new UriPath('/hello/{name}', '/hello/world'))
                )
                ->will($this->returnValue('Hello world'));
        $this->assertEquals(
                'Hello world',
                $this->createMatchingRouteWithCallback($mockProcessor)
                        ->data($this->mockRequest, $this->mockResponse)
        );
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfProcessorDoesNotImplementInterface()
    {
        $this->mockInjector->expects($this->once())
                ->method('getInstance')
                ->with($this->equalTo('\stdClass'))
                ->will($this->returnValue(new \stdClass()));
        $error = new Error('error');
        $this->mockResponse->expects($this->once())
                ->method('internalServerError')
                ->will($this->returnValue($error));
        $this->assertSame(
                $error,
                $this->createMatchingRouteWithCallback('\stdClass')
                        ->data($this->mockRequest, $this->mockResponse)
        );
    }

    /**
     * @test
     */
    public function processCreatesAndCallsGivenProcessorClass()
    {
        $mockProcessor = $this->getMock('stubbles\webapp\Processor');
        $mockProcessor->expects($this->once())
                ->method('process')
                ->with($this->equalTo($this->mockRequest),
                       $this->equalTo($this->mockResponse),
                       $this->equalTo(new UriPath('/hello/{name}', '/hello/world'))
                )
                ->will($this->returnValue('Hello world'));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo(get_class($mockProcessor)))
                           ->will($this->returnValue($mockProcessor));
        $this->assertEquals(
                'Hello world',
                $this->createMatchingRouteWithCallback(get_class($mockProcessor))
                        ->data($this->mockRequest, $this->mockResponse)
        );
    }
}

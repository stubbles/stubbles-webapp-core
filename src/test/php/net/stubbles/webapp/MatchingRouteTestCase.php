<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\SupportedMimeTypes;
/**
 * Tests for net\stubbles\webapp\MatchingRoute.
 *
 * @since  2.0.0
 * @group  core
 */
class MatchingRouteTestCase extends \PHPUnit_Framework_TestCase
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
        $this->mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                                   ->disableOriginalConstructor()
                                   ->getMock();
    }

    /**
     * creates instance to test
     *
     * @param   Route  $routeConfig
     * @return  MatchingRoute
     */
    private function createMatchingRoute(Route $routeConfig, $uri = 'http://example.com/hello/world')
    {
        return new MatchingRoute(UriRequest::fromString($uri, 'GET'),
                                 $this->getMockBuilder('net\stubbles\webapp\interceptor\Interceptors')
                                      ->disableOriginalConstructor()
                                      ->getMock(),
                                 new SupportedMimeTypes(array()),
                                 $routeConfig,
                                 $this->mockInjector
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
        $this->assertTrue($processableRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsNotHttpsAndRouteDoesNotRequireHttps()
    {
        $this->assertFalse($this->createMatchingRouteWithCallback(function() {})->switchToHttps());
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
        $this->assertFalse($processableRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals('https://example.com/hello/world',
                            (string) $this->createMatchingRouteWithCallback(function() {})->getHttpsUri()
        );
    }

    /**
     * @test
     */
    public function requiresAuthIfRouteRequiresAuth()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $processableRoute = $this->createMatchingRoute($route->withRoleOnly('admin'));
        $this->assertTrue($processableRoute->requiresAuth());
    }

    /**
     * @test
     */
    public function delegatesAuthHandlingToRoute()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('isAuthorized')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(true));
        $processableRoute = $this->createMatchingRoute($route->withRoleOnly('admin'));
        $this->assertTrue($processableRoute->isAuthorized($mockAuthHandler));
    }

    /**
     * @test
     */
    public function delegatesLoginDecisionToRoute()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('requiresLogin')
                        ->with($this->equalTo('admin'))
                        ->will($this->returnValue(true));
        $processableRoute = $this->createMatchingRoute($route->withRoleOnly('admin'));
        $this->assertTrue($processableRoute->requiresLogin($mockAuthHandler));
    }

    /**
     * creates instance to test
     *
     * @param   callable  $callback
     * @return  ProcessableRoute
     */
    private function createMatchingRouteWithCallback($callback)
    {
        return $this->createMatchingRoute(new Route('/hello/{name}',
                                                    $callback,
                                                    'GET'
                                          )
        );
    }

    /**
     * @test
     */
    public function processCallsClosureGivenAsCallback()
    {
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Hello world'));
        $this->mockInjector->expects($this->never())
                           ->method('getInstance');
        $this->createMatchingRouteWithCallback(function(WebRequest $request, Response $response, UriPath $uriPath)
                                               {
                                                   $response->setStatusCode(418)
                                                            ->write('Hello ' . $uriPath->getArgument('name'));
                                                   $request->cancel();
                                               }
               )
             ->process($this->mockRequest, $this->mockResponse);
    }

    /**
     * helper method for the test
     *
     * @param  WebRequest  $request
     * @param  Response    $response
     */
    public function theCallable(WebRequest $request, Response $response, UriPath $uriPath)
    {
        $response->setStatusCode(418)
                 ->write('Hello ' . $uriPath->getArgument('name'));
        $request->cancel();
    }

    /**
     * @test
     */
    public function processCallsGivenCallback()
    {
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Hello world'));
        $this->mockInjector->expects($this->never())
                           ->method('getInstance');
        $this->createMatchingRouteWithCallback(array($this, 'theCallable'))
             ->process($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\RuntimeException
     */
    public function processThrowsRuntimeExceptionWhenGivenProcessorClassIsNoProcessor()
    {
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('\stdClass'))
                           ->will($this->returnValue(new \stdClass()));
        $this->createMatchingRouteWithCallback('\stdClass')
             ->process($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function processCreatesAndCallsGivenProcessorClass()
    {
        $mockProcessor = $this->getMock('net\stubbles\webapp\Processor');
        $mockProcessor->expects($this->once())
                      ->method('process')
                      ->with($this->equalTo($this->mockRequest),
                             $this->equalTo($this->mockResponse),
                             $this->equalTo(new UriPath('/hello/{name}', array('name' => 'world'), null))
                        );
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo(get_class($mockProcessor)))
                           ->will($this->returnValue($mockProcessor));
        $this->createMatchingRouteWithCallback(get_class($mockProcessor))
             ->process($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function processCallsGivenProcessorInstance()
    {
        $mockProcessor = $this->getMock('net\stubbles\webapp\Processor');
        $mockProcessor->expects($this->once())
                      ->method('process')
                      ->with($this->equalTo($this->mockRequest),
                             $this->equalTo($this->mockResponse),
                             $this->equalTo(new UriPath('/hello/{name}', array('name' => 'world'), null))
                        );
        $this->mockInjector->expects($this->never())
                           ->method('getInstance');
        $this->createMatchingRouteWithCallback($mockProcessor)
             ->process($this->mockRequest, $this->mockResponse);
    }
}

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
use stubbles\webapp\UriPath;
use stubbles\webapp\UriRequest;
use stubbles\webapp\request\Request;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Tests for stubbles\webapp\routing\MatchingRoute.
 *
 * @since  2.0.0
 * @group  core
 */
class MatchingRouteTest extends \PHPUnit_Framework_TestCase
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
        $this->mockRequest  = $this->getMock('stubbles\webapp\request\Request');
        $this->mockResponse = $this->getMock('stubbles\webapp\response\Response');
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
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
        return new MatchingRoute(new UriRequest($uri, 'GET'),
                                 $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                                      ->disableOriginalConstructor()
                                      ->getMock(),
                                 new SupportedMimeTypes([]),
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
     * data provider for different return value handling
     *
     * @return  array
     */
    public function returnValueAssertions()
    {
        return [['assertFalse', false],
                ['assertTrue', true],
                ['assertTrue', null]
        ];
    }

    /**
     * @test
     * @dataProvider  returnValueAssertions
     */
    public function processCallsClosureGivenAsCallback($assert, $returnValue)
    {
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Hello world'));
        $this->$assert($this->createMatchingRouteWithCallback(function(Request $request, Response $response, UriPath $uriPath)
                                                              use($returnValue)
                                                              {
                                                                  $response->setStatusCode(418)
                                                                           ->write('Hello ' . $uriPath->readArgument('name')->asString());
                                                                  if (null !== $returnValue) {
                                                                      return $returnValue;
                                                                  }
                                                              }
                              )
                            ->process($this->mockRequest, $this->mockResponse)
        );
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
                 ->write('Hello ' . $uriPath->readArgument('name')->asString());
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
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Hello world'));
        $this->assertTrue($this->createMatchingRouteWithCallback([$this, 'theCallable'])
                               ->process($this->mockRequest, $this->mockResponse)
        );
    }

    /**
     * helper method for the test
     *
     * @param   \stubbles\webapp\request\Request   $request
     * @param   \stubbles\webapp\respone\Response  $response
     * @throws  \Exception
     */
    public function failingCallable(Request $request, Response $response, UriPath $uriPath)
    {
        throw new \Exception('some error occurred');
    }

    /**
     * @test
     * @dataProvider  returnValueAssertions
     */
    public function processCallsGivenProcessorInstance($assert, $returnValue)
    {
        $mockProcessor = $this->getMock('stubbles\webapp\Processor');
        $mocked = $mockProcessor->expects($this->once())
                                ->method('process')
                                ->with($this->equalTo($this->mockRequest),
                                       $this->equalTo($this->mockResponse),
                                       $this->equalTo(new UriPath('/hello/{name}', '/hello/world'))
                                  );
        if (null !== $returnValue) {
            $mocked->will($this->returnValue($returnValue));
        }

        $this->$assert($this->createMatchingRouteWithCallback($mockProcessor)
                            ->process($this->mockRequest, $this->mockResponse)
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
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError');
        $this->assertFalse($this->createMatchingRouteWithCallback('\stdClass')
                                ->process($this->mockRequest, $this->mockResponse)
        );
    }

    /**
     * @test
     * @dataProvider  returnValueAssertions
     */
    public function processCreatesAndCallsGivenProcessorClass($assert, $returnValue)
    {
        $mockProcessor = $this->getMock('stubbles\webapp\Processor');
        $mocked = $mockProcessor->expects($this->once())
                                ->method('process')
                                ->with($this->equalTo($this->mockRequest),
                                       $this->equalTo($this->mockResponse),
                                       $this->equalTo(new UriPath('/hello/{name}', '/hello/world'))
                                  );
        if (null !== $returnValue) {
            $mocked->will($this->returnValue($returnValue));
        }

        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo(get_class($mockProcessor)))
                           ->will($this->returnValue($mockProcessor));
        $this->$assert($this->createMatchingRouteWithCallback(get_class($mockProcessor))
                            ->process($this->mockRequest, $this->mockResponse)
        );
    }
}

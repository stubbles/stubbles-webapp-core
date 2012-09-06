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
/**
 * Tests for net\stubbles\webapp\ProcessableRoute.
 *
 * @since  2.0.0
 * @group  core
 */
class ProcessableRouteTestCase extends \PHPUnit_Framework_TestCase
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
     * @test
     */
    public function requiresSwitchToHttpsIfCalledUriIsNotHttpsButRouteRequiresHttps()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $processableRoute = new ProcessableRoute($route->httpsOnly(),
                                                 UriRequest::fromString('http://example.com/hello/world', 'GET'),
                                                 array(),
                                                 array(),
                                                 $this->mockInjector
                            );
        $this->assertTrue($processableRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsNotHttpsAndRouteDoesNotRequiresHttp()
    {
        $this->assertFalse($this->createRoute(function() {})->switchToHttps());
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
        $processableRoute = new ProcessableRoute($route->httpsOnly(),
                                                 UriRequest::fromString('https://example.com/hello/world', 'GET'),
                                                 array(),
                                                 array(),
                                                 $this->mockInjector
                            );
        $this->assertFalse($processableRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals('https://example.com/hello/world',
                            (string) $this->createRoute(function() {})->getHttpsUri()
        );
    }

    /**
     * @test
     */
    public function requiresRoleIfRouteRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $processableRoute = new ProcessableRoute($route->withRoleOnly('admin'),
                                                 UriRequest::fromString('https://example.com/hello/world', 'GET'),
                                                 array(),
                                                 array(),
                                                 $this->mockInjector
                            );
        $this->assertTrue($processableRoute->requiresRole());
    }

    /**
     * @test
     */
    public function returnsRoleFromRule()
    {
        $route = new Route('/hello/{name}',
                           function() {},
                           'GET'
                 );
        $processableRoute = new ProcessableRoute($route->withRoleOnly('admin'),
                                                 UriRequest::fromString('https://example.com/hello/world', 'GET'),
                                                 array(),
                                                 array(),
                                                 $this->mockInjector
                            );
        $this->assertEquals('admin', $processableRoute->getRequiredRole());
    }

    /**
     * creates instance to test
     *
     * @param   callable  $callback
     * @param   array     $preInterceptors
     * @param   array     $postInterceptors
     * @return  ProcessableRoute
     */
    private function createRoute($callback, array $preInterceptors = array(), array $postInterceptors = array())
    {
        return new ProcessableRoute(new Route('/hello/{name}',
                                              $callback,
                                              'GET'
                                    ),
                                    UriRequest::fromString('http://example.com/hello/world', 'GET'),
                                    $preInterceptors,
                                    $postInterceptors,

                $this->mockInjector
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
        $this->createRoute(function(WebRequest $request, Response $response, UriPath $uriPath)
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
        $this->createRoute(array($this, 'theCallable'))
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
        $this->createRoute('\stdClass')
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
        $this->createRoute(get_class($mockProcessor))
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
        $this->createRoute($mockProcessor)
             ->process($this->mockRequest, $this->mockResponse);
    }


    /**
     * a callback
     *
     * @param  WebRequest  $request
     * @param  Response    $response
     */
    public function callableMethod(WebRequest $request, Response $response)
    {
        $response->addHeader('X-Binford', '6100 (More power!)');
    }

    /**
     * @test
     */
    public function doesNotCallOtherPreInterceptorsIfOneCancelsRequest()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PreInterceptor')));
        $this->assertFalse($this->createRoute(array($this, 'theCallable'),
                                              array('some\PreInterceptor',
                                                    'other\PreInterceptor'
                                              )
                                  )
                                ->applyPreInterceptors($this->mockRequest,
                                                       $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenRequestNotCancelledByAnyPreInterceptor()
    {
        $mockPreInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PreInterceptor');
        $mockPreInterceptor->expects($this->exactly(2))
                           ->method('preProcess')
                           ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse));
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($mockPreInterceptor));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('addHeader');
        $this->assertTrue($this->createRoute(array($this, 'theCallable'),
                                             array('some\PreInterceptor',
                                                   $mockPreInterceptor,
                                                   function(WebRequest $request, Response $response)
                                                   {
                                                       $response->setStatusCode(418);
                                                   },
                                                   array($this, 'callableMethod')
                                             )
                                  )
                                ->applyPreInterceptors($this->mockRequest,
                                                       $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function doesNotCallOtherPostInterceptorsIfOneCancelsRequest()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PostInterceptor')));
        $this->assertFalse($this->createRoute(array($this, 'theCallable'),
                                              array(),
                                              array('some\PostInterceptor',
                                                    'other\PostInterceptor'
                                              )
                                  )
                                ->applyPostInterceptors($this->mockRequest,
                                                        $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenRequestNotCancelledByAnyPostInterceptor()
    {
        $mockPostInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PostInterceptor');
        $mockPostInterceptor->expects($this->exactly(2))
                            ->method('postProcess')
                            ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse));
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($mockPostInterceptor));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('addHeader');
        $this->assertTrue($this->createRoute(array($this, 'theCallable'),
                                             array(),
                                             array('some\PostInterceptor',
                                                   $mockPostInterceptor,
                                                   function(WebRequest $request, Response $response)
                                                   {
                                                       $response->setStatusCode(418);
                                                   },
                                                   array($this, 'callableMethod')
                                             )
                                  )
                                ->applyPostInterceptors($this->mockRequest,
                                                        $this->mockResponse
                                  )
        );
    }
}
?>
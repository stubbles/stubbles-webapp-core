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
use net\stubbles\peer\http\HttpUri;
use net\stubbles\webapp\response\Cookie;
use net\stubbles\webapp\response\Response;
/**
 * Helper class for the test.
 */
class TestWebApp extends WebApp
{
    /**
     * call method with given name and parameters and return its return value
     *
     * @param   string  $methodName
     * @param   string  $param1      optional
     * @param   string  $param2      optional
     * @return  Object
     */
    public static function callMethod($methodName, $param = null)
    {
        return self::$methodName($param);
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
     * configures routing for this web app
     *
     * @param  RoutingConfigurator  $routing
     */
    protected function configureRouting(RoutingConfigurator $routing)
    {
        $routing->onGet('/hello',
                        function(WebRequest $request, Response $response, UriPath $uriPath)
                        {
                            $response->write('Hello world!');
                        }
                  )
                ->preIntercept(array($this, 'callableMethod'))
                ->postIntercept('some\PostInterceptor')
                ->postIntercept(array($this, 'callableMethod'))
                ->postIntercept(function(WebRequest $request, Response $response)
                                {
                                     $response->addCookie(Cookie::create('foo', 'bar'));
                                }
                  );
        $routing->onPost('/update', function() {});
        $routing->onPut('/update', function() {});
        $routing->onGet('/admin', function() {})
                ->httpsOnly()
                ->withRoleOnly('administrator');
        $routing->onGet('/precancel',
                        function(WebRequest $request, Response $response, UriPath $uriPath)
                        {
                            $response->setStatusCode(418);
                        }
                  )
                ->preIntercept('some\PreInterceptor')
                ->preIntercept(array($this, 'callableMethod'))
                ->preIntercept(function(WebRequest $request, Response $response)
                               {
                                    $response->setStatusCode(508);
                                    $request->cancel();
                               }
                  )
                ->preIntercept('other\PreInterceptor')
                ->postIntercept(function(WebRequest $request, Response $response)
                                {
                                     $response->setStatusCode(304);
                                }
                  );
        $routing->onGet('/cancel',
                        function(WebRequest $request, Response $response, UriPath $uriPath)
                        {
                            $response->setStatusCode(418);
                            $request->cancel();
                        }
                  )
                ->preIntercept('some\PreInterceptor')
                ->preIntercept(array($this, 'callableMethod'))
                ->postIntercept(function(WebRequest $request, Response $response)
                                {
                                     $response->setStatusCode(304);
                                }
                  );
        $routing->onGet('/postcancel',
                        function(WebRequest $request, Response $response, UriPath $uriPath)
                        {
                            $response->setStatusCode(418);
                        }
                  )
                ->preIntercept(array($this, 'callableMethod'))
                ->postIntercept('some\PostInterceptor')
                ->postIntercept(function(WebRequest $request, Response $response)
                                {
                                     $response->addCookie(Cookie::create('foo', 'bar'));
                                     $request->cancel();
                                }
                  )
                ->postIntercept('other\PostInterceptor');
    }

}
/**
 * Tests for net\stubbles\webapp\WebApp.
 *
 * @since  1.7.0
 * @group  core
 */
class WebAppTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  TestWebApp
     */
    private $webApp;
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
        $this->mockRequest      = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse     = $this->getMock('net\stubbles\webapp\response\Response');
        $mockResponseNegotiator = $this->getMockBuilder('net\stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockResponseNegotiator->expects($this->any())
                               ->method('negotiate')
                               ->will($this->returnValue($this->mockResponse));
        $this->mockInjector           = $this->getMockBuilder('net\stubbles\ioc\Injector')
                                             ->disableOriginalConstructor()
                                             ->getMock();
        $this->webApp                 = new TestWebApp($this->mockRequest, $mockResponseNegotiator, $this->mockInjector);
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue($this->webApp->getClass()
                                       ->getConstructor()
                                       ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function annotationPresentOnSetAuthHandlerMethod()
    {
        $method = $this->webApp->getClass()->getMethod('setAuthHandler');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
    }

    /**
     * @test
     */
    public function canCreateIoBindingModuleWithSession()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\ioc\\IoBindingModule',
                                TestWebApp::callMethod('createIoBindingModuleWithSession')
        );
    }

    /**
     * @test
     */
    public function canCreateIoBindingModuleWithoutSession()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\ioc\\IoBindingModule',
                                TestWebApp::callMethod('createIoBindingModuleWithoutSession')
        );
    }

    /**
     * mocks request uri according to parameters
     *
     * @param  string  $uri
     * @param  string  $method
     */
    private function mockRequestUri($uri, $method = 'GET')
    {
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString($uri)));
        $this->mockRequest->expects($this->any())
                          ->method('getMethod')
                          ->will($this->returnValue($method));
    }

    /**
     * @test
     */
    public function doesNothingIfResponseNegotiationFails()
    {
        $this->mockRequestUri('http://example.net/hello');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithError404IfNoRouteFound()
    {
        $this->mockRequestUri('http://example.net/notFound');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('notFound');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithError405IfPathNotApplicableForGivenRequestMethod()
    {
        $this->mockRequestUri('http://example.net/update');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('methodNotAllowed')
                           ->with($this->equalTo('GET'), $this->equalTo(array('POST', 'PUT')));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $this->mockRequestUri('http://example.net/admin');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('https://example.net/admin'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithError500IfPathRequiresAuthButNoAuthHandlerSet()
    {
        $this->mockRequestUri('https://example.net/admin');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('Requested route requires authorization, but no auth handler defined for application'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function respondsWithRedirectToLoginUriIfRequiresAuthAndNoUserLoggedIn()
    {
        $this->mockRequestUri('https://example.net/admin');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->once())
                        ->method('isAuthorized')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->once())
                        ->method('requiresLogin')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(true));
        $mockAuthHandler->expects($this->once())
                        ->method('getLoginUri')
                        ->will($this->returnValue('http://example.net/login'));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('http://example.net/login'));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->setAuthHandler($mockAuthHandler)->run();
    }

    /**
     * @test
     */
    public function respondsWithError403IfRequiresAuthAndUserHasNoSufficientRights()
    {
        $this->mockRequestUri('https://example.net/admin');
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $mockAuthHandler = $this->getMock('net\stubbles\webapp\AuthHandler');
        $mockAuthHandler->expects($this->any())
                        ->method('isAuthorized')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->once())
                        ->method('requiresLogin')
                        ->with($this->equalTo('administrator'))
                        ->will($this->returnValue(false));
        $mockAuthHandler->expects($this->never())
                        ->method('getLoginUri');
        $this->mockResponse->expects($this->once())
                           ->method('forbidden');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->setAuthHandler($mockAuthHandler)->run();
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $this->mockRequestUri('http://example.net/precancel');
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false, true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PreInterceptor')));
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with($this->equalTo('X-Binford'));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(508));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function doesNotExecutePostInterceptorsIfRouteCancelsRequest()
    {
        $this->mockRequestUri('http://example.net/cancel');
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false, true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PreInterceptor')));
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with($this->equalTo('X-Binford'));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function doesNotExecuteAllPostInterceptorsIfPostInterceptorCancelsRequest()
    {
        $this->mockRequestUri('http://example.net/postcancel');
        $this->mockRequest->expects($this->exactly(5))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false, false, true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PostInterceptor')));
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with($this->equalTo('X-Binford'));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418));
        $this->mockResponse->expects($this->once())
                           ->method('addCookie');
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function doesExecuteEverythingIfRequestNotCancelled()
    {
        $this->mockRequestUri('http://example.net/hello');
        $this->mockRequest->expects($this->exactly(6))
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PostInterceptor')));
        $this->mockResponse->expects($this->exactly(2))
                           ->method('addHeader')
                           ->with($this->equalTo('X-Binford'));
        $this->mockResponse->expects($this->never())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Hello world!'));
        $this->mockResponse->expects($this->once())
                           ->method('addCookie');
        $this->mockRequest->expects($this->never())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function executesGetWithHeadRequestForSameUri()
    {
        $this->mockRequestUri('http://example.net/hello', 'HEAD');
        $this->mockRequest->expects($this->exactly(6))
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PostInterceptor')));
        $this->mockResponse->expects($this->exactly(2))
                           ->method('addHeader')
                           ->with($this->equalTo('X-Binford'));
        $this->mockResponse->expects($this->never())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Hello world!'));
        $this->mockResponse->expects($this->once())
                           ->method('addCookie');
        $this->mockRequest->expects($this->never())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('sendHead');
        $this->webApp->run();
    }
}
?>
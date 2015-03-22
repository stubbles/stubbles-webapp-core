<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\ioc\Binder;
use stubbles\lang\reflect;
use stubbles\peer\MalformedUriException;
use stubbles\peer\http\HttpUri;
/**
 * Helper class for the test.
 */
class TestWebApp extends WebApp
{
    /**
     * returns list of bindings required for this web app
     *
     * @return  array
     */
    public static function __bindings()
    {
        return [function(Binder $binder)
                {
                    $binder->bindConstant('stubbles.project.path')
                           ->to(self::projectPath());
                }
        ];
    }

    /**
     * returns provided request instance
     *
     * @return  \stubbles\webapp\request\Request
     */
    public function request()
    {
        return $this->request;
    }

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
     * configures routing for this web app
     *
     * @param  RoutingConfigurator  $routing
     */
    protected function configureRouting(RoutingConfigurator $routing)
    {
        // intentionally empty
    }
}
/**
 * Tests for stubbles\webapp\WebApp.
 *
 * @since  1.7.0
 * @group  core
 */
class WebAppTest extends \PHPUnit_Framework_TestCase
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
     * partially mocked routing
     *
     * @type  Routing
     */
    private $routing;
    /**
     * mocked exception logger
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockExceptionLogger;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\webapp\request\Request');
        $this->mockRequest->expects($this->any())
                          ->method('getMethod')
                          ->will($this->returnValue('GET'));
        $this->mockRequest->expects($this->any())
                          ->method('uri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.com/hello')));
        $this->mockResponse     = $this->getMock('stubbles\webapp\response\Response');
        $mockResponseNegotiator = $this->getMockBuilder('stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockResponseNegotiator->expects($this->any())
                               ->method('negotiateMimeType')
                               ->will($this->returnValue($this->mockResponse));
        $this->routing = $this->getMockBuilder('stubbles\webapp\routing\Routing')
                              ->disableOriginalConstructor()
                              ->getMock();
        $this->mockExceptionLogger = $this->getMockBuilder('stubbles\lang\errorhandler\ExceptionLogger')
                                          ->disableOriginalConstructor()
                                          ->getMock();
        $this->webApp  = $this->getMock('stubbles\webapp\TestWebApp',
                                        ['configureRouting'],
                                        [$this->mockRequest,
                                         $mockResponseNegotiator,
                                         $this->routing,
                                         $this->mockExceptionLogger
                                        ]
                         );
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        unset($_SERVER['REQUEST_METHOD']);
        restore_error_handler();
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue(
                reflect\annotationsOfConstructor($this->webApp)
                        ->contain('Inject')
        );
    }

    /**
     * @test
     */
    public function canCreateIoBindingModule()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\ioc\IoBindingModule',
                TestWebApp::callMethod('createIoBindingModule')
        );
    }

    /**
     *
     * @param type $mockRoute
     */
    private function createMockRoute()
    {
        $mockRoute = $this->getMockBuilder('stubbles\webapp\routing\ProcessableRoute')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockRoute->expects($this->once())
                  ->method('supportedMimeTypes')
                  ->will($this->returnValue(new response\SupportedMimeTypes([])));
        $this->routing->expects($this->once())
                      ->method('findRoute')
                      ->will($this->returnValue($mockRoute));
        return $mockRoute;
    }

    /**
     * @test
     */
    public function doesNothingIfResponseNegotiationFails()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->never())
                  ->method('switchToHttps');
        $mockRoute->expects($this->never())
                  ->method('applyPreInterceptors');
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockResponse->expects($this->once())
                          ->method('isFixed')
                          ->will($this->returnValue(true));
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('httpsUri')
                  ->will($this->returnValue('https://example.net/admin'));
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo('https://example.net/admin'))
                           ->will($this->returnSelf());
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPreInterceptors()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->throwException($exception));
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockExceptionLogger->expects($this->once())
                                  ->method('log')
                                  ->with($this->equalTo($exception));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('some error'));
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function doesNotExecutePostInterceptorsIfRouteCancelsRequest()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromRoute()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->throwException($exception));
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->mockExceptionLogger->expects($this->once())
                                  ->method('log')
                                  ->with($this->equalTo($exception));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('some error'));
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function doesExecuteEverythingIfRequestNotCancelled()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    );
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPostInterceptors()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
                  ->with($this->equalTo($this->mockRequest),
                         $this->equalTo($this->mockResponse)
                    )
                  ->will($this->throwException($exception));
        $this->mockExceptionLogger->expects($this->once())
                                  ->method('log')
                                  ->with($this->equalTo($exception));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('some error'));
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @since  4.0.0
     * @test
     */
    public function createCreatesInstance()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\TestWebApp',
                TestWebApp::create('projectPath')
        );
    }

    /**
     * @since  4.0.0
     * @test
     */
    public function createInstanceCreatesInstance()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\TestWebApp',
                TestWebApp::create('projectPath')
        );
    }

    /**
     * @since  5.0.0
     * @test
     */
    public function ioBindingModuleAddedByDefault()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\request\Request',
                TestWebApp::create('projectPath')->request()
        );
    }

    /**
     * @since  5.0.1
     * @test
     * @group  issue_70
     */
    public function malformedUriInRequestLeadsToResponse400BadRequest()
    {
        $mockRequest = $this->getMock('stubbles\webapp\request\Request');
        $mockRequest->expects($this->any())
                    ->method('uri')
                    ->will($this->throwException(new MalformedUriException('invalid uri')));
        $mockResponse  = $this->getMock('stubbles\webapp\response\Response');
        $mockResponseNegotiator = $this->getMockBuilder('stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockResponseNegotiator->expects($this->any())
                               ->method('negotiateMimeType')
                               ->will($this->returnValue($mockResponse));
        $mockResponse->expects($this->once())
                     ->method('setStatusCode')
                     ->with($this->equalTo(400));
        $webApp  = $this->getMock(
                'stubbles\webapp\WebApp',
                ['configureRouting'],
                [$mockRequest,
                 $mockResponseNegotiator,
                 $this->getMockBuilder('stubbles\webapp\routing\Routing')
                      ->disableOriginalConstructor()
                      ->getMock(),
                 $this->getMockBuilder('stubbles\lang\errorhandler\ExceptionLogger')
                      ->disableOriginalConstructor()
                      ->getMock()
                ]
        );
        $webApp->run();
    }
}

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
use stubbles\webapp\request\Request;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Helper class for the test.
 */
class TestWebApp extends WebApp
{
    /**
     * session to be created
     *
     * @type  \stubbles\webapp\session\Session
     */
    public static $session;

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
     * creates a session instance based on current request
     *
     * @param   \stubbles\webapp\request\Request    $request
     * @param   \stubbles\webapp\response\Response  $response
     * @return  \stubbles\webapp\session\Session
     * @since   6.0.0
     */
    protected function createSession(Request $request, Response $response)
    {
        return self::$session;
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
 * @group  core_webapp
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
     * mocked injector
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;
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
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock();
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
        $this->webApp  = new TestWebApp(
                $this->mockInjector,
                $mockResponseNegotiator,
                $this->routing,
                $this->mockExceptionLogger
        );
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/hello';
        $_SERVER['HTTP_HOST']      = 'example.com';
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        TestWebApp::$session = null;
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['HTTP_HOST']);
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
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockRoute()
    {
        $mockRoute = $this->getMockBuilder('stubbles\webapp\routing\ProcessableRoute')
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockRoute->expects($this->once())
                  ->method('supportedMimeTypes')
                  ->will($this->returnValue(new SupportedMimeTypes([])));
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
     * @since  6.0.0
     */
    public function enablesSessionScopeWhenSessionIsAvailable()
    {
        TestWebApp::$session = $this->getMock('stubbles\webapp\session\Session');
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $this->mockInjector->expects($this->once())
                           ->method('setSession')
                           ->with($this->equalTo(TestWebApp::$session));
        $this->assertSame($this->mockResponse, $this->webApp->run());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function doesNotEnableSessionScopeWhenSessionNotAvailable()
    {
        $mockRoute = $this->createMockRoute();
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $this->mockInjector->expects($this->never())
                           ->method('setSession');
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
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
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
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('process')
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
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors');
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
                  ->will($this->returnValue(true));
        $mockRoute->expects($this->once())
                  ->method('process')
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $mockRoute->expects($this->once())
                  ->method('applyPostInterceptors')
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
     * @since  5.0.1
     * @test
     * @group  issue_70
     */
    public function malformedUriInRequestLeadsToResponse400BadRequest()
    {
        $_SERVER['REQUEST_URI'] = '/hello';
        $_SERVER['HTTP_HOST']   = '%&$§!&$!§invalid';
        $mockResponseNegotiator = $this->getMockBuilder('stubbles\webapp\response\ResponseNegotiator')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $webApp  = new TestWebApp(
                $this->mockInjector,
                $mockResponseNegotiator,
                $this->routing,
                $this->mockExceptionLogger
        );
        $this->assertEquals(
                400,
                $webApp->run()->statusCode()
        );
    }
}

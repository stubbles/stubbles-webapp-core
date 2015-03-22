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
use stubbles\webapp\response\mimetypes\PassThrough;
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
     * partially mocked routing
     *
     * @type  Routing
     */
    private $routing;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->routing = $this->getMockBuilder('stubbles\webapp\routing\Routing')
                              ->disableOriginalConstructor()
                              ->getMock();
        $this->webApp  = new TestWebApp($this->mockInjector, $this->routing);
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
        $mockRoute->expects($this->any())
                  ->method('negotiateMimeType')
                  ->will($this->returnValue(new PassThrough()));
        $this->routing->expects($this->once())
                      ->method('findRoute')
                      ->will($this->returnValue($mockRoute));
        return $mockRoute;
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
        $response = $this->webApp->run();
        $this->assertEquals(302, $response->statusCode());
        $this->assertTrue(
                $response->containsHeader(
                        'Location',
                        'https://example.net/admin'
                )
        );
    }

    /**
     * @test
     */
    public function respondsWithNotAcceptableIfContentNegotiationFails  ()
    {
        $mockRoute = $this->getMockBuilder('stubbles\webapp\routing\ProcessableRoute')
                          ->disableOriginalConstructor()
                          ->getMock();
        $this->routing->expects($this->once())
                      ->method('findRoute')
                      ->will($this->returnValue($mockRoute));
        $mockRoute->expects($this->once())
                  ->method('requiresHttps')
                  ->will($this->returnValue(false));
        $mockRoute->expects($this->once())
                  ->method('negotiateMimeType')
                  ->will($this->returnValue(null));
        $mockRoute->expects($this->once())
                  ->method('supportedMimeTypes')
                  ->will($this->returnValue([]));
        $mockRoute->expects($this->never())
                  ->method('applyPreInterceptors');
        $mockRoute->expects($this->never())
                  ->method('process');
        $mockRoute->expects($this->never())
                  ->method('applyPostInterceptors');
        $response = $this->webApp->run();
        $this->assertEquals(406, $response->statusCode());

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
        $this->webApp->run();
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
        $this->webApp->run();
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
        $this->webApp->run();
    }

    /**
     * @param  \Exception  $expected
     */
    private function setUpExceptionLogger(\Exception $expected)
    {
        $mockExceptionLogger = $this->getMockBuilder('stubbles\lang\errorhandler\ExceptionLogger')
                ->disableOriginalConstructor()
                ->getMock();
        $mockExceptionLogger->expects($this->once())
                ->method('log')
                ->with($this->equalTo($expected));
        $this->mockInjector->expects($this->once())
                ->method('getInstance')
                ->will($this->returnValue($mockExceptionLogger));
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
        $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        $this->assertEquals(500, $response->statusCode());
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
        $this->webApp->run();
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
        $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        $this->assertEquals(500, $response->statusCode());
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
        $this->webApp->run();
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
        $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        $this->assertEquals(500, $response->statusCode());
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
        $webApp  = new TestWebApp($this->mockInjector, $this->routing);
        $this->assertEquals(
                400,
                $webApp->run()->statusCode()
        );
    }
}

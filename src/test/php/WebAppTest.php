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
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\Response  $response
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
    private $injector;
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
        $this->injector = $this->getMockBuilder('stubbles\ioc\Injector')
                ->disableOriginalConstructor()
                ->getMock();
        $this->routing = $this->getMockBuilder('stubbles\webapp\routing\Routing')
                ->disableOriginalConstructor()
                ->getMock();
        $this->webApp  = new TestWebApp($this->injector, $this->routing);
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
        assertTrue(
                reflect\annotationsOfConstructor($this->webApp)
                        ->contain('Inject')
        );
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function createResource()
    {
        $resource = $this->getMockBuilder('stubbles\webapp\routing\UriResource')
                ->disableOriginalConstructor()
                ->getMock();
        $resource->method('negotiateMimeType')
                ->will(returnValue(new PassThrough()));
        $this->routing->method('findResource')->will(returnValue($resource));
        return $resource;
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function createNonHttpsResource()
    {
        $resource = $this->createResource();
        $resource->method('requiresHttps')->will(returnValue(false));
        return $resource;
    }

    /**
     * @test
      */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $resource = $this->createResource();
        $resource->method('requiresHttps')->will(returnValue(true));
        $resource->method('httpsUri')
                ->will(returnValue('https://example.net/admin'));
        $response = $this->webApp->run();
        assertEquals(302, $response->statusCode());
        assertTrue(
                $response->containsHeader(
                        'Location',
                        'https://example.net/admin'
                )
        );
    }

    /**
     * @test
     */
    public function doesNotExecuteInterceptorsAndResourceIfMimeTypeNegotiationFails  ()
    {
        $resource = $this->getMockBuilder('stubbles\webapp\routing\UriResource')
                ->disableOriginalConstructor()
                ->getMock();
        $this->routing->method('findResource')->will(returnValue($resource));
        $resource->method('requiresHttps')->will(returnValue(false));
        $resource->method('negotiateMimeType')->will(returnValue(false));
        $resource->expects(never())->method('applyPreInterceptors');
        $resource->expects(never())->method('process');
        $resource->expects(never())->method('applyPostInterceptors');
        $this->webApp->run();

    }

    /**
     * @test
     * @since  6.0.0
     */
    public function enablesSessionScopeWhenSessionIsAvailable()
    {
        TestWebApp::$session = $this->getMock('stubbles\webapp\session\Session');
        $this->createNonHttpsResource();
        $this->injector->expects($this->once())
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
        $this->createNonHttpsResource();
        $this->injector->expects($this->never())->method('setSession');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $resource = $this->createNonHttpsResource();
        $resource->expects($this->once())
                ->method('applyPreInterceptors')
                ->will($this->returnValue(false));
        $resource->expects(never())->method('process');
        $resource->expects(never())->method('applyPostInterceptors');
        $this->webApp->run();
    }

    /**
     * @param  \Exception  $expected
     */
    private function setUpExceptionLogger(\Exception $expected)
    {
        $exceptionLogger = $this->getMockBuilder('stubbles\lang\errorhandler\ExceptionLogger')
                ->disableOriginalConstructor()
                ->getMock();
        $exceptionLogger->expects(once())
                ->method('log')
                ->with(equalTo($expected));
        $this->injector->method('getInstance')
                ->will(returnValue($exceptionLogger));
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPreInterceptors()
    {
        $resource = $this->createNonHttpsResource();
        $exception = new \Exception('some error');
        $resource->method('applyPreInterceptors')
                  ->will(throwException($exception));
        $resource->expects(never())->method('process');
        $resource->expects(never())->method('applyPostInterceptors');
        $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assertEquals(500, $response->statusCode());
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromRoute()
    {
        $resource = $this->createNonHttpsResource();
        $resource->expects($this->once())
                  ->method('applyPreInterceptors')
                  ->will($this->returnValue(true));
        $exception = new \Exception('some error');
        $resource->expects($this->once())
                  ->method('resolve')
                  ->will($this->throwException($exception));
        $resource->expects($this->never())
                  ->method('applyPostInterceptors');
        $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assertEquals(500, $response->statusCode());
    }

    /**
     * @test
     */
    public function doesExecuteEverythingIfRequestNotCancelled()
    {
        $resource = $this->createNonHttpsResource();
        $resource->method('applyPreInterceptors')->will(returnValue(true));
        $resource->expects(once())->method('resolve');
        $resource->expects(once())->method('applyPostInterceptors');
        $this->webApp->run();
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPostInterceptors()
    {
        $resource = $this->createNonHttpsResource();
        $resource->method('applyPreInterceptors')->will(returnValue(true));
        $resource->method('resolve')->will(returnValue(true));
        $exception = new \Exception('some error');
        $resource->method('applyPostInterceptors')
                ->will(throwException($exception));
        $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assertEquals(500, $response->statusCode());
    }

    /**
     * @since  4.0.0
     * @test
     */
    public function createCreatesInstance()
    {
        assertInstanceOf(
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
        assertInstanceOf(
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
        $webApp  = new TestWebApp($this->injector, $this->routing);
        assertEquals(
                400,
                $webApp->run()->statusCode()
        );
    }
}

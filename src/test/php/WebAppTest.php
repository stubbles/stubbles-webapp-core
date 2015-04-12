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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\ioc\Binder;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $injector;
    /**
     * partially mocked routing
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $routing;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->injector = NewInstance::stub('stubbles\ioc\Injector');
        $this->routing  = NewInstance::stub('stubbles\webapp\routing\Routing');
        $this->webApp   = new TestWebApp($this->injector, $this->routing);
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
     * @return  \bovigo\callmap\Proxy
     */
    private function createResource()
    {
        $resource = NewInstance::of('stubbles\webapp\routing\UriResource');
        $this->routing->mapCalls(['findResource' => $resource]);
        return $resource;
    }

    /**
     * @param   array  $callmap  optional
     * @return  \bovigo\callmap\Proxy
     */
    private function createNonHttpsResource(array $callmap = [])
    {
        $resource = $this->createResource();
        $resource->mapCalls(
                array_merge(
                        $callmap,
                        ['requiresHttps'     => false,
                         'negotiateMimeType' => true
                        ]
                )
        );
        return $resource;
    }

    /**
     * @test
      */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $resource = $this->createResource();
        $resource->mapCalls(
                ['requiresHttps' => true,
                 'httpsUri'      => 'https://example.net/admin'
                ]
        );
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
        $resource = NewInstance::of('stubbles\webapp\routing\UriResource');
        $this->routing->mapCalls(['findResource' => $resource]);
        $resource->mapCalls(
                ['requiresHttps'     => false,
                 'negotiateMimeType' => false
                ]
        );
        $this->webApp->run();
        callmap\verify($resource, 'applyPreInterceptors')->wasNeverCalled();
        callmap\verify($resource, 'resolve')->wasNeverCalled();
        callmap\verify($resource, 'applyPostInterceptors')->wasNeverCalled();

    }

    /**
     * @test
     * @since  6.0.0
     */
    public function enablesSessionScopeWhenSessionIsAvailable()
    {
        TestWebApp::$session = NewInstance::of('stubbles\webapp\session\Session');
        $this->createNonHttpsResource();
        $this->webApp->run();
        callmap\verify($this->injector, 'setSession')
                ->received(
                        TestWebApp::$session,
                        'stubbles\webapp\session\Session'
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function doesNotEnableSessionScopeWhenSessionNotAvailable()
    {
        $this->createNonHttpsResource();
        $this->webApp->run();
        callmap\verify($this->injector, 'setSession')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $resource = $this->createNonHttpsResource(['applyPreInterceptors' => false]);
        $this->webApp->run();
        callmap\verify($resource, 'resolve')->wasNeverCalled();
        callmap\verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    /**
     * @return  \bovigo\callmap\Proxy
     */
    private function setUpExceptionLogger()
    {
        $exceptionLogger = NewInstance::stub('stubbles\lang\errorhandler\ExceptionLogger');
        $this->injector->mapCalls(['getInstance' => $exceptionLogger]);
        return $exceptionLogger;

    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPreInterceptors()
    {
        $exception = new \Exception('some error');
        $resource  = $this->createNonHttpsResource(
                ['applyPreInterceptors' => callmap\throws($exception)]
        );
        $exceptionLogger = $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assertEquals(500, $response->statusCode());
        callmap\verify($exceptionLogger, 'log')->received($exception);
        callmap\verify($resource, 'resolve')->wasNeverCalled();
        callmap\verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromRoute()
    {
        $exception = new \Exception('some error');
        $resource = $this->createNonHttpsResource(
                ['applyPreInterceptors' => true,
                 'resolve'              => callmap\throws($exception)
                ]
        );
        $exceptionLogger = $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assertEquals(500, $response->statusCode());
        callmap\verify($exceptionLogger, 'log')->received($exception);
        callmap\verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPostInterceptors()
    {
        $exception = new \Exception('some error');
        $this->createNonHttpsResource(
                ['applyPreInterceptors'  => true,
                 'applyPostInterceptors' => callmap\throws($exception)
                ]
        );
        $exceptionLogger = $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assertEquals(500, $response->statusCode());
        callmap\verify($exceptionLogger, 'log')->received($exception);
    }

    /**
     * @test
     */
    public function executesEverythingIfRequestNotCancelled()
    {
        $resource = $this->createNonHttpsResource(
                ['applyPreInterceptors' => true]
        );
        $this->webApp->run();
        callmap\verify($resource, 'resolve')->wasCalledOnce();
        callmap\verify($resource, 'applyPostInterceptors')->wasCalledOnce();
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

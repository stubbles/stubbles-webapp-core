<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use bovigo\callmap\NewInstance;
use stubbles\ExceptionLogger;
use stubbles\ioc\{Binder, Injector};
use stubbles\peer\http\HttpUri;
use stubbles\webapp\routing\{Routing, UriResource};
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assert,
    assertTrue,
    predicate\equals,
    isInstanceOf
};
use function bovigo\callmap\{throws, verify};
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
    public static function __bindings(): array
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
        $this->injector = NewInstance::stub(Injector::class);
        $this->routing  = NewInstance::stub(Routing::class);
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

    private function createResource(): UriResource
    {
        $resource = NewInstance::of(UriResource::class);
        $this->routing->mapCalls(['findResource' => $resource]);
        return $resource;
    }

    private function createNonHttpsResource(array $callmap = []): UriResource
    {
        $resource = $this->createResource();
        $resource->mapCalls(array_merge(
                ['requiresHttps'         => false,
                 'negotiateMimeType'     => true,
                 'applyPreInterceptors'  => true,
                 'applyPostInterceptors' => true
                ],
                $callmap
        ));
        return $resource;
    }

    /**
     * @test
      */
    public function respondsWithRedirectHttpsUriIfRequiresHttps()
    {
        $resource = $this->createResource();
        $resource->mapCalls([
                'requiresHttps' => true,
                'httpsUri'      => HttpUri::fromString('https://example.net/admin')
        ]);
        $response = $this->webApp->run();
        assert($response->statusCode(), equals(302));
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
        $resource = NewInstance::of(UriResource::class);
        $this->routing->mapCalls(['findResource' => $resource]);
        $resource->mapCalls([
                'requiresHttps'     => false,
                'negotiateMimeType' => false
        ]);
        $this->webApp->run();
        verify($resource, 'applyPreInterceptors')->wasNeverCalled();
        verify($resource, 'resolve')->wasNeverCalled();
        verify($resource, 'applyPostInterceptors')->wasNeverCalled();

    }

    /**
     * @test
     * @since  6.0.0
     */
    public function enablesSessionScopeWhenSessionIsAvailable()
    {
        TestWebApp::$session = NewInstance::of(Session::class);
        $this->createNonHttpsResource();
        $this->webApp->run();
        verify($this->injector, 'setSession')->received(
                TestWebApp::$session,
                Session::class
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
        verify($this->injector, 'setSession')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $resource = $this->createNonHttpsResource(['applyPreInterceptors' => false]);
        $this->webApp->run();
        verify($resource, 'resolve')->wasNeverCalled();
        verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    /**
     * @return  \bovigo\callmap\Proxy
     */
    private function setUpExceptionLogger()
    {
        $exceptionLogger = NewInstance::stub(ExceptionLogger::class);
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
                ['applyPreInterceptors' => throws($exception)]
        );
        $exceptionLogger = $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assert($response->statusCode(), equals(500));
        verify($exceptionLogger, 'log')->received($exception);
        verify($resource, 'resolve')->wasNeverCalled();
        verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromRoute()
    {
        $exception = new \Exception('some error');
        $resource = $this->createNonHttpsResource([
                'applyPreInterceptors' => true,
                'resolve'              => throws($exception)
        ]);
        $exceptionLogger = $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assert($response->statusCode(), equals(500));
        verify($exceptionLogger, 'log')->received($exception);
        verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function sendsInternalServerErrorIfExceptionThrownFromPostInterceptors()
    {
        $exception = new \Exception('some error');
        $this->createNonHttpsResource([
                'applyPreInterceptors'  => true,
                'applyPostInterceptors' => throws($exception)

        ]);
        $exceptionLogger = $this->setUpExceptionLogger($exception);
        $response = $this->webApp->run();
        assert($response->statusCode(), equals(500));
        verify($exceptionLogger, 'log')->received($exception);
    }

    /**
     * @test
     */
    public function executesEverythingIfRequestNotCancelled()
    {
        $resource = $this->createNonHttpsResource([
                'applyPreInterceptors' => true
        ]);
        $this->webApp->run();
        verify($resource, 'resolve')->wasCalledOnce();
        verify($resource, 'applyPostInterceptors')->wasCalledOnce();
    }

    /**
     * @since  4.0.0
     * @test
     */
    public function createCreatesInstance()
    {
        assert(TestWebApp::create('projectPath'), isInstanceOf(TestWebApp::class));
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
        assert($webApp->run()->statusCode(), equals(400));
    }
}

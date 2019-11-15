<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\ExceptionLogger;
use stubbles\ioc\{Binder, Injector};
use stubbles\peer\http\HttpUri;
use stubbles\webapp\routing\{Routing, UriResource};
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assertThat,
    assertTrue,
    predicate\equals,
    predicate\isInstanceOf
};
use function bovigo\callmap\{throws, verify};
/**
 * Tests for stubbles\webapp\WebApp.
 *
 * @since  1.7.0
 * @group  core_webapp
 */
class WebAppTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  WebApp
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

    protected function setUp(): void
    {
        $this->injector = NewInstance::stub(Injector::class);
        $this->routing  = NewInstance::stub(Routing::class);
        $this->webApp   = new class($this->injector, $this->routing) extends WebApp
        {
            public static function __bindings(): array
            {
                return [function(Binder $binder)
                        {
                            $binder->bindConstant('stubbles.project.path')
                                   ->to(self::projectPath());
                        }
                ];
            }

            protected function configureRouting(RoutingConfigurator $routing): void { }
        };
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI']    = '/hello';
        $_SERVER['HTTP_HOST']      = 'example.com';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['HTTP_HOST']);
        restore_error_handler();
    }

    private function createResource(): UriResource
    {
        $resource = NewInstance::of(UriResource::class);
        $this->routing->returns(['findResource' => $resource]);
        return $resource;
    }

    private function createNonHttpsResource(array $callmap = []): UriResource
    {
        $resource = $this->createResource();
        $resource->returns(array_merge(
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
        $resource->returns([
                'requiresHttps' => true,
                'httpsUri'      => HttpUri::fromString('https://example.net/admin')
        ]);
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(302));
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
        $this->routing->returns(['findResource' => $resource]);
        $resource->returns([
                'requiresHttps'     => false,
                'negotiateMimeType' => false
        ]);
        $this->webApp->run();
        assertTrue(verify($resource, 'applyPreInterceptors')->wasNeverCalled());
        assertTrue(verify($resource, 'resolve')->wasNeverCalled());
        assertTrue(verify($resource, 'applyPostInterceptors')->wasNeverCalled());

    }

    /**
     * @test
     * @since  6.0.0
     */
    public function enablesSessionScopeWhenSessionIsAvailable()
    {
        $session = NewInstance::of(Session::class);
        $webApp  = new class($this->injector, $this->routing, $session) extends WebApp
        {
            private $session;

            public function __construct($injector, $routing, $session)
            {
                parent::__construct($injector, $routing);
                $this->session = $session;
            }

            protected function createSession(Request $request, Response $response): ?Session
            {
                return $this->session;
            }

            protected function configureRouting(RoutingConfigurator $routing): void { }
        };

        $this->createNonHttpsResource();
        $webApp->run();
        assertTrue(verify($this->injector, 'setSession')->received($session, Session::class));
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function doesNotEnableSessionScopeWhenSessionNotAvailable()
    {
        $this->createNonHttpsResource();
        $this->webApp->run();
        assertTrue(verify($this->injector, 'setSession')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest()
    {
        $resource = $this->createNonHttpsResource(['applyPreInterceptors' => false]);
        $this->webApp->run();
        assertTrue(verify($resource, 'resolve')->wasNeverCalled());
        assertTrue(verify($resource, 'applyPostInterceptors')->wasNeverCalled());
    }

    private function setUpExceptionLogger(): ExceptionLogger
    {
        $exceptionLogger = NewInstance::stub(ExceptionLogger::class);
        $this->injector->returns(['getInstance' => $exceptionLogger]);
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
        $exceptionLogger = $this->setUpExceptionLogger();
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(500));
        assertTrue(verify($exceptionLogger, 'log')->received($exception));
        assertTrue(verify($resource, 'resolve')->wasNeverCalled());
        assertTrue(verify($resource, 'applyPostInterceptors')->wasNeverCalled());
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
        $exceptionLogger = $this->setUpExceptionLogger();
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(500));
        assertTrue(verify($exceptionLogger, 'log')->received($exception));
        assertTrue(verify($resource, 'applyPostInterceptors')->wasNeverCalled());
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
        $exceptionLogger = $this->setUpExceptionLogger();
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(500));
        assertTrue(verify($exceptionLogger, 'log')->received($exception));
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
        assertTrue(verify($resource, 'resolve')->wasCalledOnce());
        assertTrue(verify($resource, 'applyPostInterceptors')->wasCalledOnce());
    }

    /**
     * @since  4.0.0
     * @test
     */
    public function createCreatesInstance()
    {
        $webAppClass = get_class($this->webApp);
        assertThat($webAppClass::create('projectPath'), isInstanceOf($webAppClass));
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
        assertThat($this->webApp->run()->statusCode(), equals(400));
    }
}

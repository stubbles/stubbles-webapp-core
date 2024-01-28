<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('core')]
class WebAppTest extends TestCase
{
    private WebApp $webApp;
    private Injector&ClassProxy $injector;
    private Routing&ClassProxy $routing;

    protected function setUp(): void
    {
        $this->injector = NewInstance::stub(Injector::class);
        $this->routing  = NewInstance::stub(Routing::class);
        $this->webApp   = new class($this->injector, $this->routing) extends WebApp
        {
            /**
             * @return  array<callable>
             */
            public static function __bindings(): array
            {
                return [
                    function(Binder $binder): void
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

    private function createResource(): UriResource&ClassProxy
    {
        $resource = NewInstance::of(UriResource::class);
        $this->routing->returns(['findResource' => $resource]);
        return $resource;
    }

    /**
     * @param  array<string,mixed>  $callmap
     */
    private function createNonHttpsResource(array $callmap = []): UriResource&ClassProxy
    {
        $resource = $this->createResource();
        $resource->returns(array_merge(
            [
                'requiresHttps'         => false,
                'negotiateMimeType'     => true,
                'applyPreInterceptors'  => true,
                'applyPostInterceptors' => true
            ],
            $callmap
        ));
        return $resource;
    }

    #[Test]
    public function respondsWithRedirectHttpsUriIfRequiresHttps(): void
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

    #[Test]
    public function doesNotExecuteInterceptorsAndResourceIfMimeTypeNegotiationFails(): void
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
     * @since  6.0.0
     */
    #[Test]
    public function enablesSessionScopeWhenSessionIsAvailable(): void
    {
        $session = NewInstance::of(Session::class);
        $webApp  = new class($this->injector, $this->routing, $session) extends WebApp
        {
            public function __construct(
                Injector $injector,
                Routing $routing,
                private Session $session
            ) {
                parent::__construct($injector, $routing);
            }

            protected function createSession(Request $request, Response $response): ?Session
            {
                return $this->session;
            }

            protected function configureRouting(RoutingConfigurator $routing): void { }
        };

        $this->createNonHttpsResource();
        $webApp->run();
        verify($this->injector, 'setSession')->received($session, Session::class);
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function doesNotEnableSessionScopeWhenSessionNotAvailable(): void
    {
        $this->createNonHttpsResource();
        $this->webApp->run();
        assertTrue(verify($this->injector, 'setSession')->wasNeverCalled());
    }

    #[Test]
    public function doesNotExecuteRouteAndPostInterceptorsIfPreInterceptorCancelsRequest(): void
    {
        $resource = $this->createNonHttpsResource(['applyPreInterceptors' => false]);
        $this->webApp->run();
        assertTrue(verify($resource, 'resolve')->wasNeverCalled());
        assertTrue(verify($resource, 'applyPostInterceptors')->wasNeverCalled());
    }

    private function setUpExceptionLogger(): ExceptionLogger&ClassProxy
    {
        $exceptionLogger = NewInstance::stub(ExceptionLogger::class);
        $this->injector->returns(['getInstance' => $exceptionLogger]);
        return $exceptionLogger;

    }

    #[Test]
    public function sendsInternalServerErrorIfExceptionThrownFromPreInterceptors(): void
    {
        $exception = new \Exception('some error');
        $resource  = $this->createNonHttpsResource(
                ['applyPreInterceptors' => throws($exception)]
        );
        $exceptionLogger = $this->setUpExceptionLogger();
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(500));
        verify($exceptionLogger, 'log')->received($exception);
        verify($resource, 'resolve')->wasNeverCalled();
        verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    #[Test]
    public function sendsInternalServerErrorIfExceptionThrownFromRoute(): void
    {
        $exception = new \Exception('some error');
        $resource = $this->createNonHttpsResource([
            'applyPreInterceptors' => true,
            'resolve'              => throws($exception)
        ]);
        $exceptionLogger = $this->setUpExceptionLogger();
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(500));
        verify($exceptionLogger, 'log')->received($exception);
        verify($resource, 'applyPostInterceptors')->wasNeverCalled();
    }

    #[Test]
    public function sendsInternalServerErrorIfExceptionThrownFromPostInterceptors(): void
    {
        $exception = new \Exception('some error');
        $this->createNonHttpsResource([
            'applyPreInterceptors'  => true,
            'applyPostInterceptors' => throws($exception)

        ]);
        $exceptionLogger = $this->setUpExceptionLogger();
        $response = $this->webApp->run();
        assertThat($response->statusCode(), equals(500));
        verify($exceptionLogger, 'log')->received($exception);
    }

    #[Test]
    public function executesEverythingIfRequestNotCancelled(): void
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
     */
    #[Test]
    public function createCreatesInstance(): void
    {
        $webAppClass = get_class($this->webApp);
        assertThat($webAppClass::create('projectPath'), isInstanceOf($webAppClass));
    }

    /**
     * @since  5.0.1 
     */
    #[Test]
    #[Group('issue_70')]
    public function malformedUriInRequestLeadsToResponse400BadRequest(): void
    {
        $_SERVER['REQUEST_URI'] = '/hello';
        $_SERVER['HTTP_HOST']   = '%&$§!&$!§invalid';
        assertThat($this->webApp->run()->statusCode(), equals(400));
    }
}

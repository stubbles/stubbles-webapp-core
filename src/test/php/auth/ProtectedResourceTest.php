<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\{Request, Response};
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\request\WebRequest;
use stubbles\webapp\response\Error;
use stubbles\webapp\routing\{RoutingAnnotations, UriResource};

use function bovigo\assert\{
    assertThat,
    assertNull,
    assertTrue,
    fail,
    predicate\equals,
    predicate\isSameAs
};
use function bovigo\callmap\{onConsecutiveCalls, throws, verify};
/**
 * Tests for stubbles\webapp\auth\ProtectedResource.
 *
 * @since  3.0.0
 * @group  auth
 */
class ProtectedResourceTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\auth\ProtectedResource
     */
    private $protectedResource;
    /**
     * route configuration
     *
     * @var  \stubbles\webapp\auth\AuthConstraint
     */
    private $authConstraint;
    /**
     * actual route to execute
     *
     * @var  UriResource&\bovigo\callmap\ClassProxy
     */
    private $actualResource;
    /**
     * @var  Injector&\bovigo\callmap\ClassProxy
     */
    private $injector;
    /**
     * @var  Request&\bovigo\callmap\ClassProxy
     */
    private $request;
    /**
     * @var  Response&\bovigo\callmap\ClassProxy
     */
    private $response;

    protected function setUp(): void
    {
        $this->authConstraint    = new AuthConstraint(new RoutingAnnotations(function() {}));
        $this->actualResource    = NewInstance::of(UriResource::class);
        $this->injector          = NewInstance::stub(Injector::class);
        $this->protectedResource = new ProtectedResource(
                $this->authConstraint,
                $this->actualResource,
                $this->injector
        );
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsWhenActualRouteDoes(): void
    {
        $this->actualResource->returns(['requiresHttps' => true]);
        assertTrue($this->protectedResource->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriOfActualRoute(): void
    {
        $httpsUri = HttpUri::fromString('https://example.com/hello');
        $this->actualResource->returns(['httpsUri' => $httpsUri]);
        assertThat($this->protectedResource->httpsUri(), isSameAs($httpsUri));
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function delegatesMimeTypeNegotiationToActualRoute(): void
    {
        $this->actualResource->returns(['negotiateMimeType' => true]);
        assertTrue(
                $this->protectedResource->negotiateMimeType(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function returnsSupportedMimeTypesOfActualRoute(): void
    {
        $this->actualResource->returns(['supportedMimeTypes' => ['application/foo']]);
        assertThat(
                $this->protectedResource->supportedMimeTypes(),
                equals(['application/foo'])
        );
    }

    /**
     * @param   array<string,mixed>  $callmap
     * @return  AuthenticationProvider
     */
    private function createAuthenticationProvider(array $callmap = []): AuthenticationProvider
    {
        $authenticationProvider = NewInstance::of(AuthenticationProvider::class);
        $this->injector->returns(['getInstance' => $authenticationProvider]);
        return $authenticationProvider->returns($callmap);
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthenticationThrowsInternalAuthProviderException(): void
    {
        $e = new InternalAuthProviderException('error');
        $this->createAuthenticationProvider(['authenticate' =>  throws($e)]);
        $this->response->returns(['internalServerError' => Error::internalServerError($e)]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals(Error::internalServerError($e))
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     * @group  issue_32
     * @group  issue_69
     */
    public function applyPreInterceptorsTriggersStatusCode504WhenAuthenticationThrowsExternalAuthHandlerException(): void
    {
        $this->createAuthenticationProvider([
                'authenticate' => throws(new ExternalAuthProviderException('error'))
        ]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals(new Error('error'))
        );
        verify($this->response, 'setStatusCode')->received(504);
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggersRedirectToLoginUriWhenNotAuthenticated(): void
    {
        $this->createAuthenticationProvider(['loginUri' => 'https://login.example.com/']);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertNull($this->protectedResource->resolve(
                $this->request,
                $this->response
        ));
        verify($this->response, 'redirect')->received('https://login.example.com/');
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     * @since  8.0.0
     * @group  issue_73
     */
    public function applyPreInterceptorsTriggers401UnauthorizedWhenNotAuthenticatedAndRedirectToLoginForbidden(): void
    {
        $this->authConstraint->sendChallengeWhenNotLoggedIn();
        $this->createAuthenticationProvider(['challengesFor' => ['Basic realm="simple"']]);
        $this->response->returns(['unauthorized' => Error::unauthorized()]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals(Error::unauthorized())
        );
        verify($this->response, 'unauthorized')->wasCalledOnce();
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndNoSpecificAuthorizationRequired(): void
    {
        $this->authConstraint->requireLogin();
        $this->createAuthenticationProvider([
                'authenticate' => NewInstance::of(User::class)
        ]);

        $this->actualResource->returns(['applyPreInterceptors' => true]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        verify($this->actualResource, 'applyPreInterceptors')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesUserInRequestIdentityWhenAuthenticated(): void
    {
        $user = NewInstance::of(User::class);
        $this->authConstraint->requireLogin();
        $this->createAuthenticationProvider(['authenticate' => $user]);
        $request = WebRequest::fromRawSource();
        $this->actualResource->returns(['applyPreInterceptors' => true]);
        $this->protectedResource->applyPreInterceptors($request, $this->response);
        $identity = $request->identity();
        if (null === $identity) {
            fail('Expected identity, got none');
        }

        assertThat($identity->user(), isSameAs($user));
    }

    /**
     * @param   Roles|callable  $roles
     * @param   User            $user
     * @return  AuthorizationProvider
     */
    private function createAuthorizationProvider($roles, User $user = null): AuthorizationProvider
    {
        $authenticationProvider = NewInstance::of(AuthenticationProvider::class)
                ->returns(['authenticate' => ($user === null) ? NewInstance::of(User::class) : $user]);
        $authorizationProvider = NewInstance::of(AuthorizationProvider::class)
                ->returns(['roles' => $roles]);
        $this->injector->returns([
                'getInstance' => onConsecutiveCalls(
                                $authenticationProvider,
                                $authorizationProvider
                )
        ]);
        return $authorizationProvider;
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthorizationThrowsAuthHandlerException(): void
    {
        $e = new InternalAuthProviderException('error');
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(throws($e));
        $this->response->returns(['internalServerError' => Error::internalServerError($e)]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals(Error::internalServerError($e))
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     * @group  issue_32
     * @group  issue_69
     */
    public function applyPreInterceptorsTriggersStatusCode504WhenAuthorizationThrowsExternalAuthHandlerException(): void
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(
                throws(new ExternalAuthProviderException('error'))
        );
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals(new Error('error'))
        );
        verify($this->response, 'setStatusCode')->received(504);
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthorized(): void
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(Roles::none());
        $this->response->returns(['forbidden' => Error::forbidden()]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals(Error::forbidden())
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndAuthorized(): void
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']));
        $this->actualResource->returns(['applyPreInterceptors' => true]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        verify($this->actualResource, 'applyPreInterceptors')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesUserInRequestIdentityWhenAuthenticatedAndAuthorized(): void
    {
        $user = NewInstance::of(User::class);
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']), $user);
        $request = WebRequest::fromRawSource();
        $this->actualResource->returns(['applyPreInterceptors' => true]);
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->response
        );
        $identity = $request->identity();
        if (null === $identity) {
            fail('Expected identity, got none');
        }

        assertThat($identity->user(), isSameAs($user));
    }

    /**
     * @test
     */
    public function storesRolesInRequestIdentityWhenAuthenticatedAndAuthorized(): void
    {
        $this->authConstraint->requireRole('admin');
        $roles = new Roles(['admin']);
        $this->createAuthorizationProvider($roles);
        $request = WebRequest::fromRawSource();
        $this->actualResource->returns(['applyPreInterceptors' => true]);
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->response
        );
        $identity = $request->identity();
        if (null === $identity) {
            fail('Expected identity, got none');
        }

        assertThat($identity->roles(), isSameAs($roles));
    }

    /**
     * @test
     */
    public function doesNotCallResolveOfActualRouteWhenNotAuthorized(): void
    {
        assertNull($this->protectedResource->resolve(
                $this->request,
                $this->response
        ));
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function resolveCallsResolveOfActualRouteWhenAuthorized(): void
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']));
        $this->actualResource->returns([
                'applyPreInterceptors' => true, 'resolve' => 'foo'
        ]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        assertThat(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                ),
                equals('foo')
        );
    }

    /**
     * @test
     */
    public function appliesPostInterceptorsWhenNotAuthorized(): void
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(Roles::none());
        $this->actualResource->returns(['applyPostInterceptors' => true]);
        $this->response->returns(['forbidden' => Error::forbidden()]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        $this->protectedResource->applyPostInterceptors(
                $this->request,
                $this->response
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'applyPostInterceptors')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function appliesPostInterceptorsWhenAuthorized(): void
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']));
        $this->actualResource->returns([
                'applyPreInterceptors'  => true,
                'resolve'               => 'foo',
                'applyPostInterceptors' => true
        ]);
        assertTrue($this->protectedResource->applyPreInterceptors(
                $this->request,
                $this->response
        ));
        $this->protectedResource->applyPostInterceptors(
                $this->request,
                $this->response
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasCalledOnce();
        verify($this->actualResource, 'applyPostInterceptors')->wasCalledOnce();
    }
}

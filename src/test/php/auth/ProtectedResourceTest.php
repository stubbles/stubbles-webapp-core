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
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\request\WebRequest;
use stubbles\webapp\response\Error;
use stubbles\webapp\routing\RoutingAnnotations;
use stubbles\webapp\routing\UriResource;

use function bovigo\assert\assert;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\callmap\onConsecutiveCalls;
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
/**
 * Tests for stubbles\webapp\auth\ProtectedResource.
 *
 * @since  3.0.0
 * @group  auth
 */
class ProtectedResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\ProtectedResource
     */
    private $protectedResource;
    /**
     * route configuration
     *
     * @type  \stubbles\webapp\auth\AuthConstraint
     */
    private $authConstraint;
    /**
     * actual route to execute
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $actualResource;
    /**
     * mocked injector
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $injector;
    /**
     * mocked request instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
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
    public function requiresSwitchToHttpsWhenActualRouteDoes()
    {
        $this->actualResource->mapCalls(['requiresHttps' => true]);
        assertTrue($this->protectedResource->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriOfActualRoute()
    {
        $httpsUri = HttpUri::fromString('https://example.com/hello');
        $this->actualResource->mapCalls(['httpsUri' => $httpsUri]);
        assert($this->protectedResource->httpsUri(), isSameAs($httpsUri));
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function delegatesMimeTypeNegotiationToActualRoute()
    {
        $this->actualResource->mapCalls(['negotiateMimeType' => true]);
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
    public function returnsSupportedMimeTypesOfActualRoute()
    {
        $this->actualResource->mapCalls(['supportedMimeTypes' => ['application/foo']]);
        assert(
                $this->protectedResource->supportedMimeTypes(),
                equals(['application/foo'])
        );
    }

    private function createAuthenticationProvider(array $callmap = []): AuthenticationProvider
    {
        $authenticationProvider = NewInstance::of(AuthenticationProvider::class);
        $this->injector->mapCalls(['getInstance' => $authenticationProvider]);
        return $authenticationProvider->mapCalls($callmap);
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthenticationThrowsInternalAuthProviderException()
    {
        $e = new InternalAuthProviderException('error');
        $this->createAuthenticationProvider(['authenticate' =>  throws($e)]);
        $this->response->mapCalls(['internalServerError' => Error::internalServerError($e)]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function applyPreInterceptorsTriggersStatusCode504WhenAuthenticationThrowsExternalAuthHandlerException()
    {
        $this->createAuthenticationProvider(
                ['authenticate' => throws(new ExternalAuthProviderException('error'))]
        );
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function applyPreInterceptorsTriggersRedirectToLoginUriWhenNotAuthenticated()
    {
        $this->createAuthenticationProvider(['loginUri' => 'https://login.example.com/']);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertNull(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
        verify($this->response, 'redirect')->received('https://login.example.com/');
        verify($this->actualResource, 'applyPreInterceptors')->wasNeverCalled();
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     * @since  5.0.0
     * @group  forbid_login
     */
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthenticatedAndRedirectToLoginForbidden()
    {
        $this->authConstraint->forbiddenWhenNotAlreadyLoggedIn();
        $this->createAuthenticationProvider();
        $this->response->mapCalls(['forbidden' => Error::forbidden()]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndNoSpecificAuthorizationRequired()
    {
        $this->authConstraint->requireLogin();
        $this->createAuthenticationProvider(
                ['authenticate' => NewInstance::of(User::class)]
        );

        $this->actualResource->mapCalls(['applyPreInterceptors' => true]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesUserInRequestIdentityWhenAuthenticated()
    {
        $user = NewInstance::of(User::class);
        $this->authConstraint->requireLogin();
        $this->createAuthenticationProvider(['authenticate' => $user]);
        $request = WebRequest::fromRawSource();
        $this->actualResource->mapCalls(['applyPreInterceptors' => true]);
        $this->protectedResource->applyPreInterceptors($request, $this->response);
        assert($request->identity()->user(), isSameAs($user));
    }

    private function createAuthorizationProvider($roles, User $user = null): AuthorizationProvider
    {
        $authenticationProvider = NewInstance::of(AuthenticationProvider::class)
                ->mapCalls(['authenticate' => ($user === null) ? NewInstance::of(User::class) : $user]);
        $authorizationProvider = NewInstance::of(AuthorizationProvider::class)
                ->mapCalls(['roles' => $roles]);
        $this->injector->mapCalls([
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
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthorizationThrowsAuthHandlerException()
    {
        $e = new InternalAuthProviderException('error');
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(throws($e));
        $this->response->mapCalls(['internalServerError' => Error::internalServerError($e)]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function applyPreInterceptorsTriggersStatusCode504WhenAuthorizationThrowsExternalAuthHandlerException()
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(
                throws(new ExternalAuthProviderException('error'))
        );
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(Roles::none());
        $this->response->mapCalls(['forbidden' => Error::forbidden()]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']));
        $this->actualResource->mapCalls(['applyPreInterceptors' => true]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function storesUserInRequestIdentityWhenAuthenticatedAndAuthorized()
    {
        $user = NewInstance::of(User::class);
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']), $user);
        $request = WebRequest::fromRawSource();
        $this->actualResource->mapCalls(['applyPreInterceptors' => true]);
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->response
        );
        assert($request->identity()->user(), isSameAs($user));
    }

    /**
     * @test
     */
    public function storesRolesInRequestIdentityWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $roles = new Roles(['admin']);
        $this->createAuthorizationProvider($roles);
        $request = WebRequest::fromRawSource();
        $this->actualResource->mapCalls(['applyPreInterceptors' => true]);
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->response
        );
        assert($request->identity()->roles(), isSameAs($roles));
    }

    /**
     * @test
     */
    public function doesNotCallResolveOfActualRouteWhenNotAuthorized()
    {
        assertNull(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
        verify($this->actualResource, 'resolve')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function resolveCallsResolveOfActualRouteWhenAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']));
        $this->actualResource->mapCalls(
                ['applyPreInterceptors' => true, 'resolve' => 'foo']
        );
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assert(
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
    public function appliesPostInterceptorsWhenNotAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(Roles::none());
        $this->actualResource->mapCalls(['applyPostInterceptors' => true]);
        $this->response->mapCalls(['forbidden' => Error::forbidden()]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
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
    public function appliesPostInterceptorsWhenAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $this->createAuthorizationProvider(new Roles(['admin']));
        $this->actualResource->mapCalls([
                'applyPreInterceptors'  => true,
                'resolve'               => 'foo',
                'applyPostInterceptors' => true
        ]);
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        $this->protectedResource->applyPostInterceptors(
                $this->request,
                $this->response
        );
        verify($this->actualResource, 'applyPreInterceptors')->wasCalledOnce();
        verify($this->actualResource, 'applyPostInterceptors')->wasCalledOnce();
    }
}

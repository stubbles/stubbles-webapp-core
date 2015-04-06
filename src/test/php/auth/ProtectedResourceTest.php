<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\request\WebRequest;
use stubbles\webapp\response\Error;
use stubbles\webapp\routing\RoutingAnnotations;
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
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $actualResource;
    /**
     * mocked injector
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $injector;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->authConstraint  = new AuthConstraint(new RoutingAnnotations(function() {}));
        $this->actualResource  = $this->getMock('stubbles\webapp\routing\UriResource');
        $this->injector    = $this->getMockBuilder('stubbles\ioc\Injector')
                ->disableOriginalConstructor()
                ->getMock();
        $this->protectedResource = new ProtectedResource(
                $this->authConstraint,
                $this->actualResource,
                $this->injector
        );
        $this->request  = $this->getMock('stubbles\webapp\Request');
        $this->response = $this->getMock('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsWhenActualRouteDoes()
    {
        $this->actualResource->method('requiresHttps')->will(returnValue(true));
        assertTrue($this->protectedResource->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriOfActualRoute()
    {
        $httpsUri = HttpUri::fromString('https://example.com/hello');
        $this->actualResource->method('httpsUri')->will(returnValue($httpsUri));
        assertSame($httpsUri, $this->protectedResource->httpsUri());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function delegatesMimeTypeNegotiationToActualRoute()
    {
        $this->actualResource->method('negotiateMimeType')->will(returnValue(true));
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
        $this->actualResource->method('supportedMimeTypes')
                ->will(returnValue(['application/foo']));
        assertEquals(
                ['application/foo'],
                $this->protectedResource->supportedMimeTypes()
        );
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAuthenticationProvider()
    {
        $authenticationProvider = $this->getMock(
                'stubbles\webapp\auth\AuthenticationProvider'
        );
        $this->injector->method('getInstance')
                ->will(returnValue($authenticationProvider));
        return $authenticationProvider;
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthenticationThrowsInternalAuthProviderException()
    {
        $e = new InternalAuthProviderException('error');
        $authenticationProvider = $this->mockAuthenticationProvider();
        $authenticationProvider->method('authenticate')
                ->will(throwException($e));
        $this->response->expects(once())
                ->method('internalServerError')
                ->with(equalTo($e))
                ->will(returnValue(Error::internalServerError($e)));
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                Error::internalServerError($e),
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     * @group  issue_32
     * @group  issue_69
     */
    public function applyPreInterceptorsTriggersStatusCode504WhenAuthenticationThrowsExternalAuthHandlerException()
    {
        $authenticationProvider = $this->mockAuthenticationProvider();
        $authenticationProvider->method('authenticate')
                ->will(throwException(new ExternalAuthProviderException('error')));
        $this->response->expects(once())
                ->method('setStatusCode')
                ->with(equalTo(504))
                ->will(returnSelf());
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                new Error('error'),
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggersRedirectToLoginUriWhenNotAuthenticated()
    {
        $authenticationProvider = $this->mockAuthenticationProvider();
        $authenticationProvider->method('authenticate')->will(returnValue(null));
        $authenticationProvider->method('loginUri')
                ->will(returnValue('https://login.example.com/'));
        $this->response->expects(once())
                ->method('redirect')
                ->with(equalTo('https://login.example.com/'));
        $this->actualResource->expects(never())->method('applyPreInterceptors');
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
    }

    /**
     * @test
     * @since  5.0.0
     * @group  forbid_login
     */
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthenticatedAndRedirectToLoginForbidden()
    {
        $this->authConstraint->forbiddenWhenNotAlreadyLoggedIn();
        $authenticationProvider = $this->mockAuthenticationProvider();
        $authenticationProvider->method('authenticate')->will(returnValue(null));
        $this->response->expects(once())
                ->method('forbidden')
                ->will(returnValue(Error::forbidden()));
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                Error::forbidden(),
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndNoSpecificAuthorizationRequired()
    {
        $this->authConstraint->requireLogin();
        $authenticationProvider = $this->mockAuthenticationProvider();
        $authenticationProvider->method('authenticate')
                ->will(returnValue($this->getMock('stubbles\webapp\auth\User')));
        $this->actualResource->expects(once())
                ->method('applyPreInterceptors')
                ->will(returnValue(true));
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function storesUserInRequestIdentityWhenAuthenticated()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->authConstraint->requireLogin();
        $authenticationProvider = $this->mockAuthenticationProvider();
        $authenticationProvider->method('authenticate')->will(returnValue($user));
        $request = WebRequest::fromRawSource();
        $this->protectedResource->applyPreInterceptors($request, $this->response);
        assertSame($user, $request->identity()->user());
    }

    /**
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAuthorizationProvider($user = null)
    {
        $authenticationProvider = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
        $authenticationProvider->method('authenticate')
                ->will(returnValue(
                        ($user === null) ? $this->getMock('stubbles\webapp\auth\User') : $user
                    )
                );
        $authorizationProvider = $this->getMock('stubbles\webapp\auth\AuthorizationProvider');
        $this->injector->method('getInstance')->will(
                $this->onConsecutiveCalls(
                        $authenticationProvider,
                        $authorizationProvider
                )
        );
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
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')->will(throwException($e));
        $this->response->expects(once())
                ->method('internalServerError')
                ->with(equalTo($e))
                ->will(returnValue(Error::internalServerError($e)));
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                Error::internalServerError($e),
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     * @group  issue_32
     * @group  issue_69
     */
    public function applyPreInterceptorsTriggersStatusCode504WhenAuthorizationThrowsExternalAuthHandlerException()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')
                ->will(throwException(new ExternalAuthProviderException('error')));
        $this->response->expects(once())
                ->method('setStatusCode')
                ->with(equalTo(504))
                ->will(returnSelf());
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                new Error('error'),
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')->will(returnValue(Roles::none()));
        $this->response->expects(once())
                ->method('forbidden')
                ->will(returnValue(Error::forbidden()));
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                Error::forbidden(),
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')
                ->will(returnValue(new Roles(['admin'])));
        $this->actualResource->expects(once())
                ->method('applyPreInterceptors')
                ->will(returnValue(true));
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function storesUserInRequestIdentityWhenAuthenticatedAndAuthorized()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider($user);
        $authorizationProvider->method('roles')
                ->will(returnValue(new Roles(['admin'])));
        $request = WebRequest::fromRawSource();
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->response
        );
        assertSame($user, $request->identity()->user());
    }

    /**
     * @test
     */
    public function storesRolesInRequestIdentityWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $roles = new Roles(['admin']);
        $authorizationProvider->method('roles')
                ->will(returnValue($roles));
        $request = WebRequest::fromRawSource();
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->response
        );
        assertSame($roles, $request->identity()->roles());
    }

    /**
     * @test
     */
    public function doesNotCallsProcessOfActualRouteWhenNotAuthorized()
    {
        $this->actualResource->expects(never())->method('process');
        assertNull(
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function processCallsProcessOfActualRouteWhenAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')
                ->will(returnValue(new Roles(['admin'])));
        $this->actualResource->expects(once())
                ->method('applyPreInterceptors')
                ->will($this->returnValue(true));
        $this->actualResource->expects(once())
                ->method('resolve')
                ->will($this->returnValue('foo'));
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertEquals(
                'foo',
                $this->protectedResource->resolve(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function appliesPostInterceptorsWhenNotAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')
                ->will(returnValue(new Roles([])));
        $this->actualResource->expects(never())->method('applyPreInterceptors');
        $this->actualResource->expects(once())
                ->method('applyPostInterceptors')
                ->will(returnValue(true));
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertTrue(
                $this->protectedResource->applyPostInterceptors(
                        $this->request,
                        $this->response
                )
        );
    }

    /**
     * @test
     */
    public function appliesPostInterceptorsWhenAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $authorizationProvider = $this->mockAuthorizationProvider();
        $authorizationProvider->method('roles')
                ->will(returnValue(new Roles(['admin'])));
        $this->actualResource->expects(once())
                ->method('applyPreInterceptors')
                ->will($this->returnValue(true));
        $this->actualResource->expects(once())
                ->method('applyPostInterceptors')
                ->will($this->returnValue(true));
        assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->request,
                        $this->response
                )
        );
        assertTrue(
                $this->protectedResource->applyPostInterceptors(
                        $this->request,
                        $this->response
                )
        );
    }
}
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
    private $mockActualRoute;
    /**
     * mocked injector
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->authConstraint   = new AuthConstraint(new RoutingAnnotations(function() {}));
        $this->mockActualRoute  = $this->getMock('stubbles\webapp\routing\UriResource');
        $this->mockInjector     = $this->getMockBuilder('stubbles\ioc\Injector')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->protectedResource = new ProtectedResource(
                $this->authConstraint,
                $this->mockActualRoute,
                $this->mockInjector
        );
        $this->mockRequest  = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse = $this->getMock('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsWhenActualRouteDoes()
    {
        $this->mockActualRoute->expects($this->once())
                              ->method('requiresHttps')
                              ->will($this->returnValue(true));
        $this->assertTrue($this->protectedResource->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriOfActualRoute()
    {
        $httpsUri = HttpUri::fromString('https://example.com/hello');
        $this->mockActualRoute->expects($this->once())
                              ->method('httpsUri')
                              ->will($this->returnValue($httpsUri));
        $this->assertSame($httpsUri, $this->protectedResource->httpsUri());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function delegatesMimeTypeNegotiationToActualRoute()
    {
        $this->mockActualRoute->expects($this->once())
                              ->method('negotiateMimeType')
                              ->will($this->returnValue(true));
        $this->assertTrue(
                $this->protectedResource->negotiateMimeType(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function returnsSupportedMimeTypesOfActualRoute()
    {
        $this->mockActualRoute->expects($this->once())
                              ->method('supportedMimeTypes')
                              ->will($this->returnValue(['application/foo']));
        $this->assertEquals(
                ['application/foo'],
                $this->protectedResource->supportedMimeTypes()
        );
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAuthenticationProvider()
    {
        $mockAuthenticationProvider = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->will($this->returnValue($mockAuthenticationProvider));
        return $mockAuthenticationProvider;
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthenticationThrowsInternalAuthProviderException()
    {
        $e = new InternalAuthProviderException('error');
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->throwException($e));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo($e))
                           ->will($this->returnValue(Error::internalServerError($e)));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                Error::internalServerError($e),
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
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
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->throwException(new ExternalAuthProviderException('error')));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(504))
                           ->will($this->returnSelf());
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                new Error('error'),
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggersRedirectToLoginUriWhenNotAuthenticated()
    {
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->returnValue(null));
        $mockAuthenticationProvider->expects($this->once())
                ->method('loginUri')
                ->will($this->returnValue('https://login.example.com/'));
        $this->mockResponse->expects($this->once())
                ->method('redirect')
                ->with($this->equalTo('https://login.example.com/'));
        $this->mockActualRoute->expects($this->never())
                ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertNull(
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
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
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->returnValue(null));
        $this->mockResponse->expects($this->once())
                ->method('forbidden')
                ->will($this->returnValue(Error::forbidden()));
        $this->mockActualRoute->expects($this->never())
                ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                Error::forbidden(),
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndNoSpecificAuthorizationRequired()
    {
        $this->authConstraint->requireLogin();
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->returnValue($this->getMock('stubbles\webapp\auth\User')));
        $this->mockActualRoute->expects($this->once())
                ->method('applyPreInterceptors')
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                ->will($this->returnValue(true));
        $this->assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
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
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->returnValue($user));
        $request = WebRequest::fromRawSource();
        $this->protectedResource->applyPreInterceptors($request, $this->mockResponse);
        $this->assertSame($user, $request->identity()->user());
    }

    /**
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockAuthorizationProvider($user = null)
    {
        $mockAuthenticationProvider = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
        $mockAuthenticationProvider->expects($this->once())
                ->method('authenticate')
                ->will($this->returnValue(
                        ($user === null) ? $this->getMock('stubbles\webapp\auth\User') : $user
                    )
                );
        $mockAuthorizationProvider = $this->getMock('stubbles\webapp\auth\AuthorizationProvider');
        $this->mockInjector->expects($this->exactly(2))
                ->method('getInstance')
                ->will($this->onConsecutiveCalls($mockAuthenticationProvider, $mockAuthorizationProvider));
        return $mockAuthorizationProvider;
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersInternalServerErrorWhenAuthorizationThrowsAuthHandlerException()
    {
        $e = new InternalAuthProviderException('error');
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->throwException($e));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo($e))
                           ->will($this->returnValue(Error::internalServerError($e)));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                Error::internalServerError($e),
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
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
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->throwException(new ExternalAuthProviderException('error')));
        $this->mockResponse->expects($this->once())
                ->method('setStatusCode')
                ->with($this->equalTo(504))
                ->will($this->returnSelf());
        $this->mockActualRoute->expects($this->never())
                ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                new Error('error'),
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggers403ForbiddenWhenNotAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->returnValue(Roles::none()));
        $this->mockResponse->expects($this->once())
                ->method('forbidden')
                ->will($this->returnValue(Error::forbidden()));
        $this->mockActualRoute->expects($this->never())
                ->method('applyPreInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                Error::forbidden(),
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsCallsActualRouteWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->returnValue(new Roles(['admin'])));
        $this->mockActualRoute->expects($this->once())
                ->method('applyPreInterceptors')
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                ->will($this->returnValue(true));
        $this->assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
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
        $mockAuthorizationProvider = $this->mockAuthorizationProvider($user);
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->returnValue(new Roles(['admin'])));
        $request = WebRequest::fromRawSource();
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->mockResponse
        );
        $this->assertSame($user, $request->identity()->user());
    }

    /**
     * @test
     */
    public function storesRolesInRequestIdentityWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $roles = new Roles(['admin']);
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->returnValue($roles));
        $request = WebRequest::fromRawSource();
        $this->protectedResource->applyPreInterceptors(
                $request,
                $this->mockResponse
        );
        $this->assertSame($roles, $request->identity()->roles());
    }

    /**
     * @test
     */
    public function doesNotCallsProcessOfActualRouteWhenNotAuthorized()
    {
        $this->mockActualRoute->expects($this->never())
                              ->method('process');
        $this->assertNull(
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function processCallsProcessOfActualRouteWhenAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->returnValue(new Roles(['admin'])));
        $this->mockActualRoute->expects($this->once())
                ->method('applyPreInterceptors')
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                ->method('resolve')
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                ->will($this->returnValue('foo'));
        $this->assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertEquals(
                'foo',
                $this->protectedResource->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function applyPostInterceptorsDoesNotCallActualRouteWhenNotAuthorized()
    {
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPostInterceptors');
        $this->assertFalse(
                $this->protectedResource->applyPostInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }

    /**
     * @test
     */
    public function applyPostInterceptorsCallsActualRouteWhenAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                ->method('roles')
                ->will($this->returnValue(new Roles(['admin'])));
        $this->mockActualRoute->expects($this->once())
                ->method('applyPreInterceptors')
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                ->will($this->returnValue(true));
        $this->mockActualRoute->expects($this->once())
                ->method('applyPostInterceptors')
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                ->will($this->returnValue(true));
        $this->assertTrue(
                $this->protectedResource->applyPreInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
        $this->assertTrue(
                $this->protectedResource->applyPostInterceptors(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }
}
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
use stubbles\webapp\auth\ioc\RolesProvider;
use stubbles\webapp\auth\ioc\UserProvider;
use stubbles\webapp\response\SupportedMimeTypes;
use stubbles\webapp\routing\RoutingAnnotations;
/**
 * Tests for stubbles\webapp\auth\AuthorizingRoute.
 *
 * @since  3.0.0
 * @group  auth
 */
class AuthorizingRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\AuthorizingRoute
     */
    private $authorizingRoute;
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
        $this->mockActualRoute  = $this->getMock('stubbles\webapp\routing\ProcessableRoute');
        $this->mockInjector     = $this->getMockBuilder('stubbles\ioc\Injector')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->authorizingRoute = new AuthorizingRoute(
                $this->authConstraint,
                $this->mockActualRoute,
                $this->mockInjector
        );
        $this->mockRequest      = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse     = $this->getMock('stubbles\webapp\response\Response');
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        RolesProvider::store(null);
        UserProvider::store(null);
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsWhenActualRouteDoes()
    {
        $this->mockActualRoute->expects($this->once())
                              ->method('requiresHttps')
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->requiresHttps());
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
        $this->assertSame($httpsUri, $this->authorizingRoute->httpsUri());
    }

    /**
     * @test
     */
    public function returnsSupportedMimeTypesOfActualRoute()
    {
        $supportedMimeTypes = new SupportedMimeTypes([]);
        $this->mockActualRoute->expects($this->once())
                              ->method('supportedMimeTypes')
                              ->will($this->returnValue($supportedMimeTypes));
        $this->assertSame($supportedMimeTypes, $this->authorizingRoute->supportedMimeTypes());
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
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                                   ->method('authenticate')
                                   ->will($this->throwException(AuthProviderException::internal('error')));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersStatusCode503WhenAuthenticationThrowsExternalAuthHandlerException()
    {
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                                   ->method('authenticate')
                                   ->will($this->throwException(AuthProviderException::external('error')));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(503))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
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
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
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
                           ->method('forbidden');
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
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
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function storesUserInUserProviderWhenAuthenticated()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->authConstraint->requireLogin();
        $mockAuthenticationProvider = $this->mockAuthenticationProvider();
        $mockAuthenticationProvider->expects($this->once())
                                   ->method('authenticate')
                                   ->will($this->returnValue($user));
        $this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse);
        $userProvider = new UserProvider();
        $this->assertSame($user, $userProvider->get());
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
                                           ($user === null) ? $this->getMock('stubbles\webapp\auth\User') : $user));
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
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                                  ->method('roles')
                                  ->will($this->throwException(AuthProviderException::internal('error')));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     * @group  issue_32
     */
    public function applyPreInterceptorsTriggersStatusCode503WhenAuthorizationThrowsExternalAuthHandlerException()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $mockAuthorizationProvider->expects($this->once())
                                  ->method('roles')
                                  ->will($this->throwException(AuthProviderException::external('error')));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(503))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('error'));
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
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
                           ->method('forbidden');
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPreInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
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
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function storesUserInUserProviderWhenAuthenticatedAndAuthorized()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider($user);
        $mockAuthorizationProvider->expects($this->once())
                                  ->method('roles')
                                  ->will($this->returnValue(new Roles(['admin'])));
        $this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse);
        $userProvider = new UserProvider();
        $this->assertSame($user, $userProvider->get());
    }

    /**
     * @test
     */
    public function storesRolesInRolesProviderWhenAuthenticatedAndAuthorized()
    {
        $this->authConstraint->requireRole('admin');
        $mockAuthorizationProvider = $this->mockAuthorizationProvider();
        $roles = new Roles(['admin']);
        $mockAuthorizationProvider->expects($this->once())
                                  ->method('roles')
                                  ->will($this->returnValue($roles));
        $this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse);
        $rolesProvider = new RolesProvider();
        $this->assertSame($roles, $rolesProvider->get());
    }

    /**
     * @test
     */
    public function doesNotCallsProcessOfActualRouteWhenNotAuthorized()
    {
        $this->mockActualRoute->expects($this->never())
                              ->method('process');
        $this->assertFalse($this->authorizingRoute->process($this->mockRequest, $this->mockResponse));
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
                              ->method('process')
                              ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                              ->will($this->returnValue(true));
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
        $this->assertTrue($this->authorizingRoute->process($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPostInterceptorsDoesNotCallActualRouteWhenNotAuthorized()
    {
        $this->mockActualRoute->expects($this->never())
                              ->method('applyPostInterceptors');
        $this->assertFalse($this->authorizingRoute->applyPostInterceptors($this->mockRequest, $this->mockResponse));
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
        $this->assertTrue($this->authorizingRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
        $this->assertTrue($this->authorizingRoute->applyPostInterceptors($this->mockRequest, $this->mockResponse));
    }
}
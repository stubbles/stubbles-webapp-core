<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\input\web\WebRequest;
use stubbles\webapp\Processor;
use stubbles\webapp\UriPath;
use stubbles\webapp\UriRequest;
use stubbles\webapp\auth\Roles;
use stubbles\webapp\response\Response;
/**
 * Class with annotations for tests.
 *
 * @RequiresHttps
 * @RequiresLogin
 * @SupportsMimeType(mimeType="text/plain")
 * @SupportsMimeType(mimeType="application/bar", formatter="example\\BarFormatter")
 * @SupportsMimeType(mimeType="application/baz", formatter=stubbles\webapp\routing\BazFormatter.class)
 */
class AnnotatedProcessor implements Processor
{
    /**
     * processes the request
     *
     * @param  WebRequest  $request   current request
     * @param  Response    $response  response to send
     * @param  UriPath     $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
class BazFormatter
{
    // intentionally empty
}
/**
 * Class with annotations for tests.
 *
 * @RequiresRole('superadmin')
 * @DisableContentNegotiation
 */
class OtherAnnotatedProcessor implements Processor
{
    /**
     * processes the request
     *
     * @param  WebRequest  $request   current request
     * @param  Response    $response  response to send
     * @param  UriPath     $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
/**
 * Class with annotations for tests.
 *
 * @RolesAware
 * @since  5.0.0
 */
class RoleAwareAnnotatedProcessor implements Processor
{
    /**
     * processes the request
     *
     * @param  WebRequest  $request   current request
     * @param  Response    $response  response to send
     * @param  UriPath     $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
/**
 * Tests for stubbles\webapp\routing\Route.
 *
 * @since  2.0.0
 * @group  core
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function constructRouteWithInvalidCallbackThrowsIllegalArgumentException()
    {
        new Route('/hello', 500, 'GET');
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  4.0.0
     */
    public function constructRouteWithInvalidRequestMethodThrowsIllegalArgumentException()
    {
        new Route('/hello', function() {}, 500);
    }

    /**
     * creates instance to test
     *
     * @param   string  $method
     * @return  \stubbles\webapp\Route
     */
    private function createRoute($method = 'GET')
    {
        return new Route('/hello/{name}',
                         function(WebRequest $request, Response $response, UriPath $uriPath)
                         {
                             $response->setStatusCode(418)
                                      ->write('Hello ' . $uriPath->readArgument('name')->asString());
                             return false;
                         },
                         $method
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainAllIfNoneGiven()
    {
        $this->assertEquals(
                ['GET', 'HEAD', 'POST', 'PUT', 'DELETE'],
                $this->createRoute(null)->allowedRequestMethods()
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainGivenSingleMethodOnly()
    {
        $this->assertEquals(['GET'], $this->createRoute()->allowedRequestMethods());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function allowedRequestMethodsContainGivenListOfMethodOnly()
    {
        $this->assertEquals(['POST', 'PUT'], $this->createRoute(['POST', 'PUT'])->allowedRequestMethods());
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestMethodsDiffer()
    {
        $this->assertFalse($this->createRoute()->matches(new UriRequest('http://example.com/hello/world', 'DELETE')));
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestPathsDiffers()
    {
        $this->assertFalse($this->createRoute()->matches(new UriRequest('http://example.com/other', 'GET')));
    }

    /**
     * @test
     */
    public function matchesIfPathAndMethodAreOk()
    {
        $this->assertTrue($this->createRoute()->matches(new UriRequest('http://example.com/hello/world', 'GET')));
    }

    /**
     * @test
     */
    public function doesNotMatchPathIfDiffers()
    {
        $this->assertFalse($this->createRoute()->matchesPath(new UriRequest('http://example.com/other', 'GET')));
    }

    /**
     * @test
     */
    public function matchesPathIfPathOk()
    {
        $this->assertTrue($this->createRoute()->matchesPath(new UriRequest('http://example.com/hello/world', 'GET')));
    }

    /**
     * @test
     */
    public function matchesForHeadIfPathOkAndAllowedMethodIsGet()
    {
        $this->assertTrue($this->createRoute()->matches(new UriRequest('http://example.com/hello/world', 'HEAD')));
    }

    /**
     * @test
     */
    public function returnsGivenPath()
    {
        $this->assertEquals('/hello/{name}', $this->createRoute()->configuredPath());
    }

    /**
     * @test
     */
    public function returnsGivenCallback()
    {
        $route = new Route('/hello/{name}', __CLASS__);
        $this->assertEquals(__CLASS__, $route->callback());
    }

    /**
     * @test
     */
    public function hasNoPreInterceptorsByDefault()
    {
        $this->assertEquals([],
                            $this->createRoute()->preInterceptors()
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException()
    {
        $this->createRoute()->preIntercept(303);
    }

    /**
     * @test
     */
    public function hasGivenListOfPreInterceptors()
    {
        $preInterceptor     = function() {};
        $mockPreInterceptor = $this->getMock('stubbles\webapp\interceptor\PreInterceptor');
        $mockPreFunction    = 'array_map';
        $this->assertEquals([get_class($mockPreInterceptor),
                             $preInterceptor,
                             $mockPreInterceptor,
                             $mockPreFunction
                            ],
                            $this->createRoute()->preIntercept(get_class($mockPreInterceptor))
                                                ->preIntercept($preInterceptor)
                                                ->preIntercept($mockPreInterceptor)
                                                ->preIntercept($mockPreFunction)
                                                ->preInterceptors()
        );
    }

    /**
     * @test
     */
    public function hasNoPostInterceptorsByDefault()
    {
        $this->assertEquals([],
                            $this->createRoute()->postInterceptors()
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException()
    {
        $this->createRoute()->postIntercept(303);
    }

    /**
     * @test
     */
    public function hasGivenListOfPostInterceptors()
    {
        $postInterceptor     = function() {};
        $mockPostInterceptor = $this->getMock('stubbles\webapp\interceptor\PostInterceptor');
        $mockPostFunction    = 'array_map';
        $this->assertEquals([get_class($mockPostInterceptor),
                             $postInterceptor,
                             $mockPostInterceptor,
                             $mockPostFunction
                            ],
                            $this->createRoute()->postIntercept(get_class($mockPostInterceptor))
                                                ->postIntercept($postInterceptor)
                                                ->postIntercept($mockPostInterceptor)
                                                ->postIntercept($mockPostFunction)
                                                ->postInterceptors()
        );
    }

    /**
     * @test
     */
    public function doesNotRequireHttpsByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresHttps());
    }

    /**
     * @test
     */
    public function requiresHttpsWhenWhenRestrictedToHttps()
    {
        $this->assertTrue($this->createRoute()->httpsOnly()->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackInstanceAnnotatedWithRequiresHttps()
    {
        $route = new Route('/hello/{name}',
                           new AnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackClassAnnotatedWithRequiresHttps()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\AnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function doesNotRequireAuthByDefault()
    {
        $this->assertFalse($this->createRoute()->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginIsRequired()
    {
        $this->assertTrue($this->createRoute()->withLoginOnly()->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresLogin()
    {
        $route = new Route('/hello/{name}',
                           new AnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresLogin()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\AnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenRoleIsRequired()
    {
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRoleAware()
    {
        $route = new Route('/hello/{name}',
                           new RoleAwareAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRoleAware()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\RoleAwareAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginAndRoleIsRequired()
    {
        $this->assertTrue($this->createRoute()->withLoginOnly()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     */
    public function doesNotRequireRolesByDefault()
    {
        $this->assertFalse($this->createRoute()->authConstraint()->requiresRoles());
    }

    /**
     * @test
     */
    public function requiresRolesWhenRoleIsSet()
    {
        $this->assertTrue($this->createRoute()->withRoleOnly('admin')->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresRolesWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresRolesWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresRolesWhenCallbackInstanceAnnotatedWithRoleAware()
    {
        $route = new Route('/hello/{name}',
                           new RoleAwareAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresRolesWhenCallbackClassAnnotatedWithRoleAware()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\RoleAwareAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isNotSatisfiedByRolesWhenRolesAreNull()
    {
        $this->assertFalse($this->createRoute()->authConstraint()->satisfiedByRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesAwareWithCallbackClass()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\RoleAwareAnnotatedProcessor',
                           'GET'
                 );
        $this->assertFalse($route->authConstraint()->satisfiedByRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesAwareWithCallbackInstance()
    {
        $route = new Route('/hello/{name}',
                           new RoleAwareAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->satisfiedByRoles(new Roles([])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesContainRequiredRoleFromAnnotatedCallbackInstance()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->satisfiedByRoles(new Roles(['admin', 'superadmin'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isNotSatisfiedByRolesWhenRolesDoNotContainRequiredRoleFromAnnotatedCallbackInstance()
    {
        $route = new Route('/hello/{name}',
                           new OtherAnnotatedProcessor(),
                           'GET'
                 );
        $this->assertFalse($route->authConstraint()->satisfiedByRoles(new Roles(['user'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesContainRequiredRoleFromAnnotatedCallbackClass()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertTrue($route->authConstraint()->satisfiedByRoles(new Roles(['admin', 'superadmin'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isNotSatisfiedByRolesWhenRolesDoNotContainRequiredRoleFromAnnotatedCallbackClass()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertFalse($route->authConstraint()->satisfiedByRoles(new Roles(['user'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  forbid_login
     */
    public function forbiddenWhenNotAlreadyLoggedInSetsInfoOnAuthConstraint()
    {
        $route = new Route('/hello/{name}',
                           'stubbles\webapp\routing\OtherAnnotatedProcessor',
                           'GET'
                 );
        $this->assertFalse($route->forbiddenWhenNotAlreadyLoggedIn()->authConstraint()->loginAllowed());
    }

    /**
     * @test
     */
    public function supportNoMimeTypeByDefault()
    {
        $this->assertEquals([],
                            $this->createRoute()
                                 ->supportedMimeTypes()
                                 ->asArray()
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  5.0.0
     */
    public function addMimeTypeWithoutFormatterWhenNoDefaultFormatterIsKnownThrowsInvalidArgumentException()
    {
        $this->createRoute()->supportsMimeType('application/foo');
    }

    /**
     * @test
     */
    public function returnsListOfAddedSupportedMimeTypes()
    {
        $this->assertEquals(['application/json', 'application/xml'],
                            $this->createRoute()
                                 ->supportsMimeType('application/json')
                                 ->supportsMimeType('application/xml')
                                 ->supportedMimeTypes()
                                 ->asArray()
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function supportedMimeTypesContainSpecialFormatter()
    {
        $this->assertTrue($this->createRoute()
                               ->supportsMimeType('foo/bar', 'example\FooBarFormatter')
                               ->supportedMimeTypes()
                               ->provideFormatter('foo/bar')
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function supportedMimeTypesContainSpecialFormatterClass()
    {
        $this->assertEquals('example\FooBarFormatter',
                            $this->createRoute()
                                 ->supportsMimeType('foo/bar', 'example\FooBarFormatter')
                                 ->supportedMimeTypes()
                                 ->formatterFor('foo/bar')
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsEnabledByDefault()
    {
        $this->assertFalse($this->createRoute()->supportedMimeTypes()->isContentNegotationDisabled());
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        $this->assertTrue($this->createRoute()
                               ->disableContentNegotiation()
                               ->supportedMimeTypes()
                               ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     * @group  issue_63
     * @since  5.1.0
     */
    public function contentNegotationIsDisabledWhenProcessorAnnotated()
    {
        $route = new Route('/hello', 'stubbles\webapp\routing\OtherAnnotatedProcessor', 'GET');
        $this->assertTrue($route->supportedMimeTypes()->isContentNegotationDisabled());
    }

    /**
     * @test
     * @group  issue_63
     * @since  5.1.0
     */
    public function listOfSupportedMimeTypesContainsAnnotatedMimeTypes()
    {
        $route = new Route('/hello', 'stubbles\webapp\routing\AnnotatedProcessor', 'GET');
        $this->assertEquals(
                ['text/plain', 'application/bar', 'application/baz'],
                $route->supportedMimeTypes()->asArray()
        );
    }

    /**
     * @return
     */
    public function formatters()
    {
        return [
            ['example\BarFormatter', 'application/bar'],
            ['stubbles\webapp\routing\BazFormatter', 'application/baz']
        ];
    }

    /**
     * @test
     * @group  issue_63
     * @dataProvider  formatters
     * @since  5.1.0
     */
    public function listOfSupportedMimeTypesContainsFormatterForAnnotatedMimeTypes($expectedFormatter, $mimeType)
    {
        $route = new Route('/hello', 'stubbles\webapp\routing\AnnotatedProcessor', 'GET');
        $this->assertEquals(
                $expectedFormatter,
                $route->supportedMimeTypes()->formatterFor($mimeType)
        );
    }

    /**
     * @test
     * @group  issue_63
     * @since  5.1.0
     */
    public function annotatedMimeTypeFormatterCanBeOverwritten()
    {
        $route = new Route('/hello', 'stubbles\webapp\routing\AnnotatedProcessor', 'GET');
        $this->assertEquals(
                'example\OtherBarFormatter',
                $route->supportsMimeType('application/bar', 'example\OtherBarFormatter')
                      ->supportedMimeTypes()
                      ->formatterFor('application/bar')
        );
    }
}

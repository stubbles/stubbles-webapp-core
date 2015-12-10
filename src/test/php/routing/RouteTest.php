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
use bovigo\callmap\NewInstance;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\Target;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\auth\AuthConstraint;
use stubbles\webapp\auth\Roles;
use stubbles\webapp\interceptor\PreInterceptor;
use stubbles\webapp\interceptor\PostInterceptor;
use stubbles\webapp\routing\api\Header;
use stubbles\webapp\routing\api\Parameter;
use stubbles\webapp\routing\api\Status;
/**
 * Class with annotations for tests.
 *
 * @RequiresHttps
 * @RequiresLogin
 * @Name('Orders')
 * @Description('List of placed orders')
 * @SupportsMimeType(mimeType="text/plain")
 * @SupportsMimeType(mimeType="application/bar", class="example\\Bar")
 * @SupportsMimeType(mimeType="application/baz", class=stubbles\webapp\routing\Baz.class)
 * @Status(code=200, description='Default status code')
 * @Status(code=404, description='No orders found')
 * @Parameter(name='foo', in='path', description='Some path parameter', required=true)
 * @Parameter(name='bar', in='query', description='A query parameter')
 * @Header(name='Last-Modified', description='Some explanation')
 * @Header(name='X-Binford', description='More power!')
 */
class AnnotatedProcessor implements Target
{
    /**
     * processes the request
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath   $uriPath   information about called uri path
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
class Baz
{
    // intentionally empty
}
/**
 * Class with annotations for tests.
 *
 * @RequiresRole('superadmin')
 * @DisableContentNegotiation
 */
class OtherAnnotatedProcessor implements Target
{
    /**
     * processes the request
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\respone\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath           $uriPath   information about called uri path
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
/**
 * Class with annotations for tests.
 *
 * @RolesAware
 * @ExcludeFromApiIndex
 * @since  5.0.0
 */
class RoleAwareAnnotatedProcessor implements Target
{
    /**
     * processes the request
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\respone\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath           $uriPath   information about called uri path
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath)
    {
        // intentionally empty
    }
}
/**
 * Tests for stubbles\webapp\routing\Route.
 *
 * @since  2.0.0
 * @group  routing
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
        return new Route(
                '/hello/{name}',
                function(Request $request, Response $response, UriPath $uriPath)
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
        assertEquals(
                ['GET', 'HEAD', 'POST', 'PUT', 'DELETE'],
                $this->createRoute(null)->allowedRequestMethods()
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainGivenSingleMethodOnly()
    {
        assertEquals(['GET'], $this->createRoute()->allowedRequestMethods());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function allowedRequestMethodsContainGivenListOfMethodOnly()
    {
        assertEquals(
                ['POST', 'PUT'],
                $this->createRoute(['POST', 'PUT']
        )->allowedRequestMethods());
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestMethodsDiffer()
    {
        assertFalse(
                $this->createRoute()->matches(
                        new CalledUri('http://example.com/hello/world', 'DELETE')
                )
        );
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestPathsDiffers()
    {
        assertFalse(
                $this->createRoute()->matches(
                        new CalledUri('http://example.com/other', 'GET')
                )
        );
    }

    /**
     * @test
     */
    public function matchesIfPathAndMethodAreOk()
    {
        assertTrue(
                $this->createRoute()->matches(
                        new CalledUri('http://example.com/hello/world', 'GET')
                )
        );
    }

    /**
     * @test
     */
    public function doesNotMatchPathIfDiffers()
    {
        assertFalse(
                $this->createRoute()->matchesPath(
                        new CalledUri('http://example.com/other', 'GET')
                )
        );
    }

    /**
     * @test
     */
    public function matchesPathIfPathOk()
    {
        assertTrue(
                $this->createRoute()->matchesPath(
                        new CalledUri('http://example.com/hello/world', 'GET')
                )
        );
    }

    /**
     * @test
     */
    public function matchesForHeadIfPathOkAndAllowedMethodIsGet()
    {
        assertTrue(
                $this->createRoute()->matches(
                        new CalledUri('http://example.com/hello/world', 'HEAD')
                )
        );
    }

    /**
     * @test
     */
    public function returnsGivenPath()
    {
        assertEquals(
                '/hello/{name}',
                $this->createRoute()->configuredPath()
        );
    }

    /**
     * @test
     */
    public function returnsGivenCallback()
    {
        $route = new Route('/hello/{name}', __CLASS__);
        assertEquals(__CLASS__, $route->target());
    }

    /**
     * @test
     */
    public function hasNoPreInterceptorsByDefault()
    {
        assertEquals([], $this->createRoute()->preInterceptors());
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
        $preInterceptorClosure  = function() {};
        $preInterceptor         = NewInstance::of(PreInterceptor::class);
        $preInterceptorFunction = 'array_map';
        assertEquals(
                [get_class($preInterceptor),
                 $preInterceptorClosure,
                 $preInterceptor,
                 $preInterceptorFunction
                ],
                $this->createRoute()->preIntercept(get_class($preInterceptor))
                        ->preIntercept($preInterceptorClosure)
                        ->preIntercept($preInterceptor)
                        ->preIntercept($preInterceptorFunction)
                        ->preInterceptors()
        );
    }

    /**
     * @test
     */
    public function hasNoPostInterceptorsByDefault()
    {
        assertEquals([], $this->createRoute()->postInterceptors());
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
        $postInterceptorClosure  = function() {};
        $postInterceptor         = NewInstance::of(PostInterceptor::class);
        $postInterceptorFunction = 'array_map';
        assertEquals(
                [get_class($postInterceptor),
                 $postInterceptorClosure,
                 $postInterceptor,
                 $postInterceptorFunction
                ],
                $this->createRoute()->postIntercept(get_class($postInterceptor))
                        ->postIntercept($postInterceptorClosure)
                        ->postIntercept($postInterceptor)
                        ->postIntercept($postInterceptorFunction)
                        ->postInterceptors()
        );
    }

    /**
     * @test
     */
    public function doesNotRequireHttpsByDefault()
    {
        assertFalse($this->createRoute()->requiresHttps());
    }

    /**
     * @test
     */
    public function requiresHttpsWhenWhenRestrictedToHttps()
    {
        assertTrue($this->createRoute()->httpsOnly()->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackInstanceAnnotatedWithRequiresHttps()
    {
        $route = new Route('/hello/{name}', new AnnotatedProcessor(), 'GET');
        assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackClassAnnotatedWithRequiresHttps()
    {
        $route = new Route(
                '/hello/{name}',
                AnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function doesNotRequireAuthByDefault()
    {
        assertFalse($this->createRoute()->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginIsRequired()
    {
        assertTrue($this->createRoute()->withLoginOnly()->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresLogin()
    {
        $route = new Route('/hello/{name}', new AnnotatedProcessor(), 'GET');
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresLogin()
    {
        $route = new Route('/hello/{name}',
                           AnnotatedProcessor::class,
                           'GET'
                 );
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenRoleIsRequired()
    {
        assertTrue($this->createRoute()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route('/hello/{name}', new OtherAnnotatedProcessor(), 'GET');
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route(
                '/hello/{name}',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRoleAware()
    {
        $route = new Route(
                '/hello/{name}',
                new RoleAwareAnnotatedProcessor(),
                'GET'
        );
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRoleAware()
    {
        $route = new Route(
                '/hello/{name}',
                RoleAwareAnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginAndRoleIsRequired()
    {
        assertTrue(
                $this->createRoute()
                        ->withLoginOnly()
                        ->withRoleOnly('admin')
                        ->requiresAuth()
        );
    }

    /**
     * @test
     */
    public function doesNotRequireRolesByDefault()
    {
        assertFalse($this->createRoute()->authConstraint()->requiresRoles());
    }

    /**
     * @test
     */
    public function requiresRolesWhenRoleIsSet()
    {
        assertTrue(
                $this->createRoute()
                        ->withRoleOnly('admin')
                        ->authConstraint()
                        ->requiresRoles()
        );
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresRolesWhenCallbackInstanceAnnotatedWithRequiresRole()
    {
        $route = new Route(
                '/hello/{name}',
                new OtherAnnotatedProcessor(),
                'GET'
        );
        assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresRolesWhenCallbackClassAnnotatedWithRequiresRole()
    {
        $route = new Route(
                '/hello/{name}',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresRolesWhenCallbackInstanceAnnotatedWithRoleAware()
    {
        $route = new Route(
                '/hello/{name}',
                new RoleAwareAnnotatedProcessor(),
                'GET'
        );
        assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function requiresRolesWhenCallbackClassAnnotatedWithRoleAware()
    {
        $route = new Route(
                '/hello/{name}',
                RoleAwareAnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->authConstraint()->requiresRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isNotSatisfiedByRolesWhenRolesAreNull()
    {
        assertFalse($this->createRoute()->authConstraint()->satisfiedByRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesAwareWithCallbackClass()
    {
        $route = new Route(
                '/hello/{name}',
                RoleAwareAnnotatedProcessor::class,
                'GET'
        );
        assertFalse($route->authConstraint()->satisfiedByRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesAwareWithCallbackInstance()
    {
        $route = new Route(
                '/hello/{name}',
                new RoleAwareAnnotatedProcessor(),
                'GET'
        );
        assertTrue($route->authConstraint()->satisfiedByRoles(new Roles([])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesContainRequiredRoleFromAnnotatedCallbackInstance()
    {
        $route = new Route(
                '/hello/{name}',
                new OtherAnnotatedProcessor(),
                'GET'
        );
        assertTrue($route->authConstraint()->satisfiedByRoles(new Roles(['admin', 'superadmin'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isNotSatisfiedByRolesWhenRolesDoNotContainRequiredRoleFromAnnotatedCallbackInstance()
    {
        $route = new Route(
                '/hello/{name}',
                new OtherAnnotatedProcessor(),
                'GET'
        );
        assertFalse($route->authConstraint()->satisfiedByRoles(new Roles(['user'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesContainRequiredRoleFromAnnotatedCallbackClass()
    {
        $route = new Route(
                '/hello/{name}',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->authConstraint()->satisfiedByRoles(new Roles(['admin', 'superadmin'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isNotSatisfiedByRolesWhenRolesDoNotContainRequiredRoleFromAnnotatedCallbackClass()
    {
        $route = new Route(
                '/hello/{name}',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertFalse($route->authConstraint()->satisfiedByRoles(new Roles(['user'])));
    }

    /**
     * @test
     * @since  5.0.0
     * @group  forbid_login
     */
    public function forbiddenWhenNotAlreadyLoggedInSetsInfoOnAuthConstraint()
    {
        $route = new Route(
                '/hello/{name}',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertFalse($route->forbiddenWhenNotAlreadyLoggedIn()->authConstraint()->loginAllowed());
    }

    /**
     * @test
     */
    public function supportNoMimeTypeByDefault()
    {
        assertEquals(
                [],
                $this->createRoute()->supportedMimeTypes()->asArray()
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  5.0.0
     */
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException()
    {
        $this->createRoute()->supportsMimeType('application/foo');
    }

    /**
     * @test
     */
    public function returnsListOfAddedSupportedMimeTypes()
    {
        assertEquals(
                ['application/json', 'application/xml'],
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
    public function supportedMimeTypesContainSpecialClass()
    {
        assertTrue(
                $this->createRoute()
                        ->supportsMimeType('foo/bar', 'example\FooBar')
                        ->supportedMimeTypes()
                        ->provideClass('foo/bar')
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function supportedMimeTypesReturnSpecialClass()
    {
        assertEquals(
                'example\FooBar',
                $this->createRoute()
                        ->supportsMimeType('foo/bar', 'example\FooBar')
                        ->supportedMimeTypes()
                        ->classFor('foo/bar')
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsEnabledByDefault()
    {
        assertFalse(
                $this->createRoute()
                        ->supportedMimeTypes()
                        ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        assertTrue(
                $this->createRoute()
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
        $route = new Route(
                '/hello',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertTrue(
                $route->supportedMimeTypes()->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     * @group  issue_63
     * @since  5.1.0
     */
    public function listOfSupportedMimeTypesContainsAnnotatedMimeTypes()
    {
        $route = new Route(
                '/hello',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                ['text/plain', 'application/bar', 'application/baz'],
                $route->supportedMimeTypes()->asArray()
        );
    }

    /**
     * @return
     */
    public function mimeTypeClasses()
    {
        return [
            ['example\Bar', 'application/bar'],
            [Baz::class, 'application/baz']
        ];
    }

    /**
     * @test
     * @group  issue_63
     * @dataProvider  mimeTypeClasses
     * @since  5.1.0
     */
    public function listOfSupportedMimeTypesContainsClassForAnnotatedMimeTypes($expectedMimeTypeClass, $mimeType)
    {
        $route = new Route(
                '/hello',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                $expectedMimeTypeClass,
                $route->supportedMimeTypes()->classFor($mimeType)
        );
    }

    /**
     * @test
     * @group  issue_63
     * @since  5.1.0
     */
    public function annotatedMimeTypeClassCanBeOverwritten()
    {
        $route = new Route(
                '/hello',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                'example\OtherBar',
                $route->supportsMimeType('application/bar', 'example\OtherBar')
                      ->supportedMimeTypes()
                      ->classFor('application/bar')
        );
    }

    /**
     * @return  array
     */
    public function resources()
    {
        return [
            [
                AnnotatedProcessor::class,
                'Orders',
                ['text/plain', 'application/bar', 'application/baz'],
            ],
            [
                new AnnotatedProcessor(),
                'Orders',
                ['text/plain', 'application/bar', 'application/baz'],
            ],
            [
                OtherAnnotatedProcessor::class,
                'OtherAnnotatedProcessor',
                [],
            ],
            [
                new OtherAnnotatedProcessor(), 'OtherAnnotatedProcessor', [],
            ],
            [
                function() {}, null, []
            ]
        ];
    }

    /**
     * @test
     * @since  6.1.0
     * @dataProvider  resources
     */
    public function routeCanBeRepresentedAsResource($target, $name, array $mimeTypes)
    {
        $route = new Route(
                '/orders',
                $target,
                'GET'
        );
        $annotations = new RoutingAnnotations($target);
        assertEquals(
                new api\Resource(
                        $name,
                        ['GET'],
                        HttpUri::fromString('https://example.com/orders'),
                        $mimeTypes,
                        $annotations,
                        new AuthConstraint($annotations)
                ),
                $route->asResource(HttpUri::fromString('https://example.com/'))
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function normalizesPathForResource()
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                'https://example.com/orders/',
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri()
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function uriTransformedToHttpsWhenHttpsRequired()
    {
        $route = new Route(
                '/orders/?$',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        $route->httpsOnly();
        assertEquals(
                'https://example.com/orders/',
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri()
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function uriNotTransformedToHttpsWhenHttpsNotRequired()
    {
        $route = new Route(
                '/orders/?$',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                'http://example.com/orders/',
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri()
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function routeIsIncludedInApiIndexByDefault()
    {
        $route = new Route(
                '/orders/?$',
                function() {},
                'GET'
        );
        assertFalse($route->shouldBeIgnoredInApiIndex());
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function routeCanBeExcludedFromApiIndexViaSwitch()
    {
        $route = new Route(
                '/orders/?$',
                function() {},
                'GET'
        );
        assertTrue($route->excludeFromApiIndex()->shouldBeIgnoredInApiIndex());
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function routeCanBeExcludedFromApiIndexViaAnnotation()
    {
        $route = new Route(
                '/orders/?$',
                RoleAwareAnnotatedProcessor::class,
                'GET'
        );
        assertTrue($route->shouldBeIgnoredInApiIndex());
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function resourceRepresentationContainsListOfSupportedMimeTypes()
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        $route->supportsMimeType('application/xml');
        assertEquals(
                ['text/plain',
                 'application/bar',
                 'application/baz',
                 'application/xml'
                ],
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->mimeTypes()
        );
    }

    /**
     * @test
     * @since  6.2.1
     */
    public function resourceRepresentationContainsListOfSupportedMimeTypesIncludingGlobal()
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        $route->supportsMimeType('application/xml');
        assertEquals(
                ['text/plain',
                 'application/bar',
                 'application/baz',
                 'application/xml',
                 'application/foo'
                ],
                $route->asResource(HttpUri::fromString('http://example.com/'), ['application/foo'])
                        ->mimeTypes()
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function resourceRepresentationContainsListOfStatusCodes()
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                [new Status(200, 'Default status code'), new Status(404, 'No orders found')],
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->statusCodes()
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function resourceRepresenationContainsListOfHeaders()
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                [new Header('Last-Modified', 'Some explanation'),
                 new Header('X-Binford', 'More power!')
                ],
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->headers()
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function resourceRepresenationContainsListOfParameters()
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertEquals(
                [(new Parameter('foo', 'Some path parameter', 'path'))->markRequired(),
                 new Parameter('bar', 'A query parameter', 'query')
                ],
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->parameters()
        );
    }
}

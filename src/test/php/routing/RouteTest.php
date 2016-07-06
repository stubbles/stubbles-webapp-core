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

use function bovigo\assert\assert;
use function bovigo\assert\assertEmptyArray;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\expect;
use function bovigo\assert\predicate\equals;
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
     */
    public function constructRouteWithInvalidCallbackThrowsIllegalArgumentException()
    {
        expect(function() { new Route('/hello', 500, 'GET'); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function constructRouteWithInvalidRequestMethodThrowsIllegalArgumentException()
    {
        expect(function() { new Route('/hello', function() {}, 500); })
                ->throws(\InvalidArgumentException::class);
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
        assert(
                $this->createRoute(null)->allowedRequestMethods(),
                equals(['GET', 'HEAD', 'POST', 'PUT', 'DELETE'])
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainGivenSingleMethodOnly()
    {
        assert($this->createRoute()->allowedRequestMethods(), equals(['GET']));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function allowedRequestMethodsContainGivenListOfMethodOnly()
    {
        assert(
                $this->createRoute(['POST', 'PUT'])->allowedRequestMethods(),
                equals(['POST', 'PUT'])
        );
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
        assert($this->createRoute()->configuredPath(), equals('/hello/{name}'));
    }

    /**
     * @test
     */
    public function returnsGivenCallback()
    {
        $route = new Route('/hello/{name}', __CLASS__);
        assert($route->target(), equals(__CLASS__));
    }

    /**
     * @test
     */
    public function hasNoPreInterceptorsByDefault()
    {
        assertEmptyArray($this->createRoute()->preInterceptors());
    }

    /**
     * @test
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException()
    {
        expect(function() { $this->createRoute()->preIntercept(303); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function hasGivenListOfPreInterceptors()
    {
        $preInterceptorClosure  = function() {};
        $preInterceptor         = NewInstance::of(PreInterceptor::class);
        $preInterceptorFunction = 'array_map';
        assert(
                $this->createRoute()->preIntercept(get_class($preInterceptor))
                        ->preIntercept($preInterceptorClosure)
                        ->preIntercept($preInterceptor)
                        ->preIntercept($preInterceptorFunction)
                        ->preInterceptors(),
                equals([
                        get_class($preInterceptor),
                        $preInterceptorClosure,
                        $preInterceptor,
                        $preInterceptorFunction
                ])
        );
    }

    /**
     * @test
     */
    public function hasNoPostInterceptorsByDefault()
    {
        assertEmptyArray($this->createRoute()->postInterceptors());
    }

    /**
     * @test
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException()
    {
        expect(function() { $this->createRoute()->postIntercept(303); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function hasGivenListOfPostInterceptors()
    {
        $postInterceptorClosure  = function() {};
        $postInterceptor         = NewInstance::of(PostInterceptor::class);
        $postInterceptorFunction = 'array_map';
        assert(
                $this->createRoute()->postIntercept(get_class($postInterceptor))
                        ->postIntercept($postInterceptorClosure)
                        ->postIntercept($postInterceptor)
                        ->postIntercept($postInterceptorFunction)
                        ->postInterceptors(),
                equals([
                        get_class($postInterceptor),
                        $postInterceptorClosure,
                        $postInterceptor,
                        $postInterceptorFunction
                ])
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
        assertEmptyArray($this->createRoute()->supportedMimeTypes()->asArray());
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException()
    {
        expect(function() {
                $this->createRoute()->supportsMimeType('application/foo');
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function returnsListOfAddedSupportedMimeTypes()
    {
        assert(
                $this->createRoute()
                        ->supportsMimeType('application/json')
                        ->supportsMimeType('application/xml')
                        ->supportedMimeTypes()
                        ->asArray(),
                equals(['application/json', 'application/xml'])
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
        assert(
                $this->createRoute()
                        ->supportsMimeType('foo/bar', 'example\FooBar')
                        ->supportedMimeTypes()
                        ->classFor('foo/bar'),
                equals('example\FooBar')
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
        assertTrue($route->supportedMimeTypes()->isContentNegotationDisabled());
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
        assert(
                $route->supportedMimeTypes()->asArray(),
                equals(['text/plain', 'application/bar', 'application/baz'])
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
        assert(
                $route->supportedMimeTypes()->classFor($mimeType),
                equals($expectedMimeTypeClass)
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
        assert(
                $route->supportsMimeType('application/bar', 'example\OtherBar')
                        ->supportedMimeTypes()
                        ->classFor('application/bar'),
                equals('example\OtherBar')
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
        assert(
                $route->asResource(HttpUri::fromString('https://example.com/')),
                equals(new api\Resource(
                        $name,
                        ['GET'],
                        HttpUri::fromString('https://example.com/orders'),
                        $mimeTypes,
                        $annotations,
                        new AuthConstraint($annotations)
                ))
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri(),
                equals('https://example.com/orders/')
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri(),
                equals('https://example.com/orders/')
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri(),
                equals('http://example.com/orders/')
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->mimeTypes(),
                equals([
                        'text/plain',
                        'application/bar',
                        'application/baz',
                        'application/xml'
                ])
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'), ['application/foo'])
                        ->mimeTypes(),
                equals([
                        'text/plain',
                        'application/bar',
                        'application/baz',
                        'application/xml',
                        'application/foo'
                ])
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->statusCodes(),
                equals([
                        new Status(200, 'Default status code'),
                        new Status(404, 'No orders found')
                ])
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->headers(),
                equals([
                        new Header('Last-Modified', 'Some explanation'),
                        new Header('X-Binford', 'More power!')
                ])
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
        assert(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->parameters(),
                equals([
                        (new Parameter('foo', 'Some path parameter', 'path'))->markRequired(),
                        new Parameter('bar', 'A query parameter', 'query')
                ])
        );
    }
}

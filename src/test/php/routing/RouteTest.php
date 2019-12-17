<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\helper\routing\{AnnotatedProcessor, Baz, OtherAnnotatedProcessor, RoleAwareAnnotatedProcessor};
use stubbles\peer\http\HttpUri;
use stubbles\webapp\{Request, Response, Target, UriPath};
use stubbles\webapp\auth\{AuthConstraint, Roles};
use stubbles\webapp\interceptor\{PreInterceptor, PostInterceptor};
use stubbles\webapp\routing\api\{Header, Parameter, Status};

use function bovigo\assert\{
    assertThat,
    assertEmptyArray,
    assertFalse,
    assertTrue,
    expect,
    predicate\equals
};
/**
 * Tests for stubbles\webapp\routing\Route.
 *
 * @since  2.0.0
 * @group  routing
 */
class RouteTest extends TestCase
{
    /**
     * @test
     */
    public function constructRouteWithInvalidCallbackThrowsIllegalArgumentException(): void
    {
        expect(function() { new Route('/hello', 500, 'GET'); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function constructRouteWithInvalidRequestMethodThrowsIllegalArgumentException(): void
    {
        expect(function() { new Route('/hello', function() {}, 500); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @param   string|string[]  $method
     * @return  Route
     */
    private function createRoute($method = 'GET'): Route
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
    public function allowedRequestMethodsContainAllIfNoneGiven(): void
    {
        assertThat(
                $this->createRoute(null)->allowedRequestMethods(),
                equals(['GET', 'HEAD', 'POST', 'PUT', 'DELETE'])
        );
    }

    /**
     * @test
     */
    public function allowedRequestMethodsContainGivenSingleMethodOnly(): void
    {
        assertThat($this->createRoute()->allowedRequestMethods(), equals(['GET']));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function allowedRequestMethodsContainGivenListOfMethodOnly(): void
    {
        assertThat(
                $this->createRoute(['POST', 'PUT'])->allowedRequestMethods(),
                equals(['POST', 'PUT'])
        );
    }

    /**
     * @test
     */
    public function doesNotMatchUriRequestIfRequestMethodsDiffer(): void
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
    public function doesNotMatchUriRequestIfRequestPathsDiffers(): void
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
    public function matchesIfPathAndMethodAreOk(): void
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
    public function doesNotMatchPathIfDiffers(): void
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
    public function matchesPathIfPathOk(): void
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
    public function matchesForHeadIfPathOkAndAllowedMethodIsGet(): void
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
    public function returnsGivenPath(): void
    {
        assertThat($this->createRoute()->configuredPath(), equals('/hello/{name}'));
    }

    /**
     * @test
     */
    public function returnsGivenCallback(): void
    {
        $route = new Route('/hello/{name}', __CLASS__);
        assertThat($route->target(), equals(__CLASS__));
    }

    /**
     * @test
     */
    public function hasNoPreInterceptorsByDefault(): void
    {
        assertEmptyArray($this->createRoute()->preInterceptors());
    }

    /**
     * @test
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException(): void
    {
        expect(function() { $this->createRoute()->preIntercept(303); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function hasGivenListOfPreInterceptors(): void
    {
        $preInterceptorClosure  = function() {};
        $preInterceptor         = NewInstance::of(PreInterceptor::class);
        $preInterceptorFunction = 'array_map';
        assertThat(
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
    public function hasNoPostInterceptorsByDefault(): void
    {
        assertEmptyArray($this->createRoute()->postInterceptors());
    }

    /**
     * @test
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException(): void
    {
        expect(function() { $this->createRoute()->postIntercept(303); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function hasGivenListOfPostInterceptors(): void
    {
        $postInterceptorClosure  = function() {};
        $postInterceptor         = NewInstance::of(PostInterceptor::class);
        $postInterceptorFunction = 'array_map';
        assertThat(
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
    public function doesNotRequireHttpsByDefault(): void
    {
        assertFalse($this->createRoute()->requiresHttps());
    }

    /**
     * @test
     */
    public function requiresHttpsWhenWhenRestrictedToHttps(): void
    {
        assertTrue($this->createRoute()->httpsOnly()->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackInstanceAnnotatedWithRequiresHttps(): void
    {
        $route = new Route('/hello/{name}', new AnnotatedProcessor(), 'GET');
        assertTrue($route->requiresHttps());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresHttpsWhenCallbackClassAnnotatedWithRequiresHttps(): void
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
    public function doesNotRequireAuthByDefault(): void
    {
        assertFalse($this->createRoute()->requiresAuth());
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function requiresAuthWhenLoginIsRequired(): void
    {
        assertTrue($this->createRoute()->withLoginOnly()->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresLogin(): void
    {
        $route = new Route('/hello/{name}', new AnnotatedProcessor(), 'GET');
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresLogin(): void
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
    public function requiresAuthWhenRoleIsRequired(): void
    {
        assertTrue($this->createRoute()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresRole(): void
    {
        $route = new Route('/hello/{name}', new OtherAnnotatedProcessor(), 'GET');
        assertTrue($route->requiresAuth());
    }

    /**
     * @test
     * @since  3.1.0
     */
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresRole(): void
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
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRoleAware(): void
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
    public function requiresAuthWhenCallbackClassAnnotatedWithRoleAware(): void
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
    public function requiresAuthWhenLoginAndRoleIsRequired(): void
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
    public function doesNotRequireRolesByDefault(): void
    {
        assertFalse($this->createRoute()->authConstraint()->requiresRoles());
    }

    /**
     * @test
     */
    public function requiresRolesWhenRoleIsSet(): void
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
    public function requiresRolesWhenCallbackInstanceAnnotatedWithRequiresRole(): void
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
    public function requiresRolesWhenCallbackClassAnnotatedWithRequiresRole(): void
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
    public function requiresRolesWhenCallbackInstanceAnnotatedWithRoleAware(): void
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
    public function requiresRolesWhenCallbackClassAnnotatedWithRoleAware(): void
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
    public function isNotSatisfiedByRolesWhenRolesAreNull(): void
    {
        assertFalse($this->createRoute()->authConstraint()->satisfiedByRoles());
    }

    /**
     * @test
     * @since  5.0.0
     * @group  role_aware
     */
    public function isSatisfiedByRolesWhenRolesAwareWithCallbackClass(): void
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
    public function isSatisfiedByRolesWhenRolesAwareWithCallbackInstance(): void
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
    public function isSatisfiedByRolesWhenRolesContainRequiredRoleFromAnnotatedCallbackInstance(): void
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
    public function isNotSatisfiedByRolesWhenRolesDoNotContainRequiredRoleFromAnnotatedCallbackInstance(): void
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
    public function isSatisfiedByRolesWhenRolesContainRequiredRoleFromAnnotatedCallbackClass(): void
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
    public function isNotSatisfiedByRolesWhenRolesDoNotContainRequiredRoleFromAnnotatedCallbackClass(): void
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
    public function forbiddenWhenNotAlreadyLoggedInSetsInfoOnAuthConstraint(): void
    {
        $route = new Route(
                '/hello/{name}',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertFalse($route->sendChallengeWhenNotLoggedIn()->authConstraint()->redirectToLogin());
    }

    /**
     * @test
     */
    public function supportNoMimeTypeByDefault(): void
    {
        assertEmptyArray($this->createRoute()->supportedMimeTypes()->asArray());
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException(): void
    {
        expect(function() {
                $this->createRoute()->supportsMimeType('application/foo');
        })->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function returnsListOfAddedSupportedMimeTypes(): void
    {
        assertThat(
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
    public function supportedMimeTypesContainSpecialClass(): void
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
    public function supportedMimeTypesReturnSpecialClass(): void
    {
        assertThat(
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
    public function contentNegotationIsEnabledByDefault(): void
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
    public function contentNegotationCanBeDisabled(): void
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
    public function contentNegotationIsDisabledWhenProcessorAnnotated(): void
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
    public function listOfSupportedMimeTypesContainsAnnotatedMimeTypes(): void
    {
        $route = new Route(
                '/hello',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
                $route->supportedMimeTypes()->asArray(),
                equals(['text/plain', 'application/bar', 'application/baz'])
        );
    }

    /**
     * @return  array<mixed[]>
     */
    public function mimeTypeClasses(): array
    {
        return [
            ['example\\\Bar', 'application/bar'],
            [Baz::class, 'application/baz']
        ];
    }

    /**
     * @test
     * @group  issue_63
     * @dataProvider  mimeTypeClasses
     * @since  5.1.0
     */
    public function listOfSupportedMimeTypesContainsClassForAnnotatedMimeTypes(
            string $expectedMimeTypeClass,
            string $mimeType
    ): void {
        $route = new Route(
                '/hello',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
                $route->supportedMimeTypes()->classFor($mimeType),
                equals($expectedMimeTypeClass)
        );
    }

    /**
     * @test
     * @group  issue_63
     * @since  5.1.0
     */
    public function annotatedMimeTypeClassCanBeOverwritten(): void
    {
        $route = new Route(
                '/hello',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
                $route->supportsMimeType('application/bar', 'example\OtherBar')
                        ->supportedMimeTypes()
                        ->classFor('application/bar'),
                equals('example\OtherBar')
        );
    }

    /**
     * @return  array<mixed[]>
     */
    public function resources(): array
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
     * @param  class-string<Target>|Target|callable  $target
     * @param  string                                $name
     * @param  string[]                              $mimeTypes
     * @test
     * @since  6.1.0
     * @dataProvider  resources
     */
    public function routeCanBeRepresentedAsResource($target, ?string $name, array $mimeTypes): void
    {
        $route = new Route(
                '/orders',
                $target,
                'GET'
        );
        $annotations = new RoutingAnnotations($target);
        assertThat(
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
    public function normalizesPathForResource(): void
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri(),
                equals('https://example.com/orders/')
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function uriTransformedToHttpsWhenHttpsRequired(): void
    {
        $route = new Route(
                '/orders/?$',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        $route->httpsOnly();
        assertThat(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri(),
                equals('https://example.com/orders/')
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function uriNotTransformedToHttpsWhenHttpsNotRequired(): void
    {
        $route = new Route(
                '/orders/?$',
                OtherAnnotatedProcessor::class,
                'GET'
        );
        assertThat(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->links()->with('self')[0]->uri(),
                equals('http://example.com/orders/')
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function routeIsIncludedInApiIndexByDefault(): void
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
    public function routeCanBeExcludedFromApiIndexViaSwitch(): void
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
    public function routeCanBeExcludedFromApiIndexViaAnnotation(): void
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
    public function resourceRepresentationContainsListOfSupportedMimeTypes(): void
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        $route->supportsMimeType('application/xml');
        assertThat(
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
    public function resourceRepresentationContainsListOfSupportedMimeTypesIncludingGlobal(): void
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        $route->supportsMimeType('application/xml');
        assertThat(
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
    public function resourceRepresentationContainsListOfStatusCodes(): void
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
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
    public function resourceRepresenationContainsListOfHeaders(): void
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
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
    public function resourceRepresenationContainsListOfParameters(): void
    {
        $route = new Route(
                '/orders/?$',
                AnnotatedProcessor::class,
                'GET'
        );
        assertThat(
                $route->asResource(HttpUri::fromString('http://example.com/'))
                        ->parameters(),
                equals([
                        (new Parameter('foo', 'Some path parameter', 'path'))->markRequired(),
                        new Parameter('bar', 'A query parameter', 'query')
                ])
        );
    }
}

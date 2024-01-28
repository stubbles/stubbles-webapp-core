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
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
     * @param  string|string[]|null  $method
     */
    private function createRoute(string|array|null $method = 'GET'): Route
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

    #[Test]
    public function allowedRequestMethodsContainAllIfNoneGiven(): void
    {
        assertThat(
                $this->createRoute(null)->allowedRequestMethods(),
                equals(['GET', 'HEAD', 'POST', 'PUT', 'DELETE'])
        );
    }

    #[Test]
    public function allowedRequestMethodsContainGivenSingleMethodOnly(): void
    {
        assertThat($this->createRoute()->allowedRequestMethods(), equals(['GET']));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function allowedRequestMethodsContainGivenListOfMethodOnly(): void
    {
        assertThat(
                $this->createRoute(['POST', 'PUT'])->allowedRequestMethods(),
                equals(['POST', 'PUT'])
        );
    }

    #[Test]
    public function doesNotMatchUriRequestIfRequestMethodsDiffer(): void
    {
        assertFalse(
                $this->createRoute()->matches(
                        new CalledUri('http://example.com/hello/world', 'DELETE')
                )
        );
    }

    #[Test]
    public function doesNotMatchUriRequestIfRequestPathsDiffers(): void
    {
        assertFalse(
            $this->createRoute()->matches(
                new CalledUri('http://example.com/other', 'GET')
            )
        );
    }

    #[Test]
    public function matchesIfPathAndMethodAreOk(): void
    {
        assertTrue(
            $this->createRoute()->matches(
                new CalledUri('http://example.com/hello/world', 'GET')
            )
        );
    }

    #[Test]
    public function doesNotMatchPathIfDiffers(): void
    {
        assertFalse(
            $this->createRoute()->matchesPath(
                new CalledUri('http://example.com/other', 'GET')
            )
        );
    }

    #[Test]
    public function matchesPathIfPathOk(): void
    {
        assertTrue(
            $this->createRoute()->matchesPath(
                new CalledUri('http://example.com/hello/world', 'GET')
            )
        );
    }

    #[Test]
    public function matchesForHeadIfPathOkAndAllowedMethodIsGet(): void
    {
        assertTrue(
            $this->createRoute()->matches(
                new CalledUri('http://example.com/hello/world', 'HEAD')
            )
        );
    }

    #[Test]
    public function returnsGivenPath(): void
    {
        assertThat($this->createRoute()->configuredPath(), equals('/hello/{name}'));
    }

    #[Test]
    public function returnsGivenCallback(): void
    {
        $route = new Route('/hello/{name}', __CLASS__);
        assertThat($route->target(), equals(__CLASS__));
    }

    #[Test]
    public function hasNoPreInterceptorsByDefault(): void
    {
        assertEmptyArray($this->createRoute()->preInterceptors());
    }

    #[Test]
    public function hasGivenListOfPreInterceptors(): void
    {
        $preInterceptorClosure  = function() {};
        $preInterceptorClass    = NewInstance::classname(PreInterceptor::class);
        $preInterceptor         = NewInstance::of(PreInterceptor::class);
        $preInterceptorFunction = 'array_map';
        assertThat(
            $this->createRoute()->preIntercept($preInterceptorClass)
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

    #[Test]
    public function hasNoPostInterceptorsByDefault(): void
    {
        assertEmptyArray($this->createRoute()->postInterceptors());
    }

    #[Test]
    public function hasGivenListOfPostInterceptors(): void
    {
        $postInterceptorClosure  = function() {};
        $postInterceptorClass    = NewInstance::classname(PostInterceptor::class);
        $postInterceptor         = NewInstance::of(PostInterceptor::class);
        $postInterceptorFunction = 'array_map';
        assertThat(
            $this->createRoute()->postIntercept($postInterceptorClass)
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

    #[Test]
    public function doesNotRequireHttpsByDefault(): void
    {
        assertFalse($this->createRoute()->requiresHttps());
    }

    #[Test]
    public function requiresHttpsWhenWhenRestrictedToHttps(): void
    {
        assertTrue($this->createRoute()->httpsOnly()->requiresHttps());
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    public function requiresHttpsWhenCallbackInstanceAnnotatedWithRequiresHttps(): void
    {
        $route = new Route('/hello/{name}', new AnnotatedProcessor(), 'GET');
        assertTrue($route->requiresHttps());
    }

    /**
     * @since  3.1.0
     */
    #[Test]
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
     * @since  3.0.0
     */
    #[Test]
    public function doesNotRequireAuthByDefault(): void
    {
        assertFalse($this->createRoute()->requiresAuth());
    }

    /**
     * @since  3.0.0
     */
    #[Test]
    public function requiresAuthWhenLoginIsRequired(): void
    {
        assertTrue($this->createRoute()->withLoginOnly()->requiresAuth());
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresLogin(): void
    {
        $route = new Route('/hello/{name}', new AnnotatedProcessor(), 'GET');
        assertTrue($route->requiresAuth());
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    public function requiresAuthWhenCallbackClassAnnotatedWithRequiresLogin(): void
    {
        $route = new Route('/hello/{name}',
                           AnnotatedProcessor::class,
                           'GET'
                 );
        assertTrue($route->requiresAuth());
    }

    /**
     * @since  3.0.0
     */
    #[Test]
    public function requiresAuthWhenRoleIsRequired(): void
    {
        assertTrue($this->createRoute()->withRoleOnly('admin')->requiresAuth());
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    public function requiresAuthWhenCallbackInstanceAnnotatedWithRequiresRole(): void
    {
        $route = new Route('/hello/{name}', new OtherAnnotatedProcessor(), 'GET');
        assertTrue($route->requiresAuth());
    }

    /**
     * @since  3.1.0
     */
    #[Test]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  3.0.0
     */
    #[Test]
    public function requiresAuthWhenLoginAndRoleIsRequired(): void
    {
        assertTrue(
            $this->createRoute()
                ->withLoginOnly()
                ->withRoleOnly('admin')
                ->requiresAuth()
        );
    }

    #[Test]
    public function doesNotRequireRolesByDefault(): void
    {
        assertFalse($this->createRoute()->authConstraint()->requiresRoles());
    }

    #[Test]
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
     * @since  3.1.0
     */
    #[Test]
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
     * @since  3.1.0
     */
    #[Test]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
    public function isNotSatisfiedByRolesWhenRolesAreNull(): void
    {
        assertFalse($this->createRoute()->authConstraint()->satisfiedByRoles());
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
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
     * @since  5.0.0
     */
    #[Test]
    #[Group('role_aware')]
    public function forbiddenWhenNotAlreadyLoggedInSetsInfoOnAuthConstraint(): void
    {
        $route = new Route(
            '/hello/{name}',
            OtherAnnotatedProcessor::class,
            'GET'
        );
        assertFalse($route->sendChallengeWhenNotLoggedIn()->authConstraint()->redirectToLogin());
    }

    #[Test]
    public function supportNoMimeTypeByDefault(): void
    {
        assertEmptyArray($this->createRoute()->supportedMimeTypes()->asArray());
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException(): void
    {
        expect(function() {
            $this->createRoute()->supportsMimeType('application/foo');
        })->throws(\InvalidArgumentException::class);
    }

    #[Test]
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
     * @since  3.2.0
     */
    #[Test]
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
     * @since  3.2.0
     */
    #[Test]
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
     * @since  2.1.1
     */
    #[Test]
    public function contentNegotationIsEnabledByDefault(): void
    {
        assertFalse(
            $this->createRoute()
                ->supportedMimeTypes()
                ->isContentNegotationDisabled()
        );
    }

    /**
     * @since  2.1.1
     */
    #[Test]
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
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_63')]
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
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_63')]
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

    public static function provideMimeTypeClasses(): Generator
    {
        yield ['example\\\Bar', 'application/bar'];
        yield [Baz::class, 'application/baz'];
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_63')]
    #[DataProvider('provideMimeTypeClasses')]
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
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_63')]
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

    public static function provideResources(): Generator
    {
        yield [
            AnnotatedProcessor::class,
            'Orders',
            ['text/plain', 'application/bar', 'application/baz'],
        ];
        yield [
            new AnnotatedProcessor(),
            'Orders',
            ['text/plain', 'application/bar', 'application/baz'],
        ];
        yield [
            OtherAnnotatedProcessor::class,
            'OtherAnnotatedProcessor',
            [],
        ];
        yield [
            new OtherAnnotatedProcessor(), 'OtherAnnotatedProcessor', [],
        ];
        yield [
            function() {}, null, []
        ];
    }

    /**
     * @param  class-string<Target>|Target|callable  $target
     * @param  string                                $name
     * @param  string[]                              $mimeTypes
     * @since  6.1.0
     */
    #[Test]
    #[Group('issue_63')]
    #[DataProvider('provideResources')]
    public function routeCanBeRepresentedAsResource(
        string|callable|Target $target,
        ?string $name,
        array $mimeTypes
    ): void {
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.2.1
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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
     * @since  6.1.0
     */
    #[Test]
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

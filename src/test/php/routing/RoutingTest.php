<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\input\ValueReader;
use stubbles\ioc\Injector;
use stubbles\webapp\{Request, Response};
use stubbles\webapp\auth\ProtectedResource;
use stubbles\webapp\response\WebResponse;
use stubbles\webapp\response\mimetypes\Json;
use stubbles\webapp\routing\api\Index;

use function bovigo\assert\{
    assertThat,
    assertEmptyArray,
    assertTrue,
    expect,
    predicate\contains,
    predicate\doesNotContain,
    predicate\equals,
    predicate\isInstanceOf
};
use function bovigo\callmap\verify;
/**
 * Tests for stubbles\webapp\routing\Routing.
 *
 * @since  2.0.0
 */
#[Group('routing')]
class RoutingTest extends TestCase
{
    private Routing $routing;
    private Injector&ClassProxy $injector;
    private CalledUri $calledUri;

    protected function setUp(): void
    {
        SupportedMimeTypes::removeDefaultMimeTypeClass('application/foo');
        $this->injector  = NewInstance::stub(Injector::class);
        $this->routing   = new Routing($this->injector);
        $this->calledUri = new CalledUri('http://example.net/hello', 'GET');
    }

    protected function tearDown(): void
    {
        SupportedMimeTypes::removeDefaultMimeTypeClass('application/foo');
    }

    #[Test]
    public function returnsNotFoundOnRouteSelectionWhenNoRouteAdded(): void
    {
        assertThat(
            $this->routing->findResource($this->calledUri),
            isInstanceOf(NotFound::class)
        );
    }

    #[Test]
    public function returnsNotFoundOnRouteSelectionWhenNoSuitableRouteAdded(): void
    {
        $this->routing->onHead('/bar', function() {});
        $this->routing->onGet('/foo', function() {});
        assertThat(
            $this->routing->findResource($this->calledUri),
            isInstanceOf(NotFound::class)
        );
    }

    /**
     * @since  2.2.0
     */
    #[Test]
    public function returnsResourceOptionsOnRouteSelectionWhenNoSuitableRouteForMethodAddedButIsOptionsRequest(): void
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        assertThat(
            $this->routing->findResource('http://example.net/hello', 'OPTIONS'),
            isInstanceOf(ResourceOptions::class)
        );
    }

    /**
     * @since  2.2.0
     */
    #[Test]
    public function returnsMethodNotAllowedOnRouteSelectionWhenNoSuitableRouteForMethodAdded(): void
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        assertThat(
            $this->routing->findResource($this->calledUri),
            isInstanceOf(MethodNotAllowed::class)
        );
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    public function returnsProtectedResourceWhenMatchingRouteRequiresLogin(): void
    {
        $this->routing->onGet('/hello', function() {})->withLoginOnly();
        assertThat(
            $this->routing->findResource($this->calledUri),
            isInstanceOf(ProtectedResource::class)
        );
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    public function returnsProtectedResourceWhenMatchingRouteRequiresRole(): void
    {
        $this->routing->onGet('/hello', function() {})->withRoleOnly('admin');
        assertThat(
            $this->routing->findResource($this->calledUri),
            isInstanceOf(ProtectedResource::class)
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function routeWithoutMethodRestrictionReturnsResourceOptionsOnOptionRequest(): void
    {
        $this->routing->onAll('/hello', function() { });
        assertThat(
            $this->routing->findResource('http://example.net/hello', 'OPTIONS'),
            isInstanceOf(ResourceOptions::class)
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function routeWithoutMethodRestrictionProvidesListOfAllMethodsOnOptionRequest(): void
    {
        $this->routing->onAll('/hello', function() { });
        $response = NewInstance::of(Response::class);
        $this->routing->findResource('http://example.net/hello', 'OPTIONS')
                ->resolve(NewInstance::of(Request::class), $response);
        verify($response, 'addHeader')
                ->received('Allow', 'GET, HEAD, POST, PUT, DELETE, OPTIONS');
    }

    /**
     * @param   array<callable|class-string<PreInterceptor>|PreInterceptor>  $preInterceptors
     * @param   array<callable|class-string<PostInterceptor>|PostInterceptor>  $postInterceptors
     */
    private function createResolvingResource(
        Route $route,
        array $preInterceptors = [],
        array $postInterceptors = [],
        string $path = 'hello'
    ): ResolvingResource {
        $injector = NewInstance::stub(Injector::class);
        return new ResolvingResource(
            $injector,
            new CalledUri('http://example.net/' . $path, 'GET'),
            new Interceptors($injector, $preInterceptors, $postInterceptors),
            new SupportedMimeTypes([]),
            $route
        );
    }

    #[Test]
    public function returnsRouteWhichFitsMethodAndPath(): void
    {
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource($route);
        assertThat($this->routing->findResource($this->calledUri), equals($resource));
    }

    #[Test]
    public function hasNoGlobalPreInterceptorsForDifferentMethod(): void
    {
        $preInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource($route);
        $this->routing->preInterceptOnHead($preInterceptor)
            ->preInterceptOnPost($preInterceptor)
            ->preInterceptOnPut($preInterceptor)
            ->preInterceptOnDelete($preInterceptor);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function hasGlobalPreInterceptorsEvenWhenNoRouteSelected(): void
    {
        $preInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource(
            $route,
            ['array_map', $preInterceptor]
        );
        $this->routing->preIntercept('array_map')->preInterceptOnGet($preInterceptor);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    /**
     * @since  3.4.0
     */
    #[Test]
    public function hasGlobalPreInterceptorsWithMatchingPath(): void
    {
        $preInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource(
            $route,
            ['array_map']
        );
        $this->routing->preIntercept('array_map', '/hello')
            ->preInterceptOnGet($preInterceptor, '/world');
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function hasGlobalPreInterceptorsEvenWhenRouteSelected(): void
    {
        $preInterceptor = function() {};
        $preFunction    = 'array_map';
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource(
            $route,
            [$preInterceptor, $preFunction]
        );
        $this->routing->preIntercept($preInterceptor)
            ->preIntercept($preFunction);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function mergesGlobalAndRoutePreInterceptors(): void
    {
        $preInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {})
            ->preIntercept('array_map');
        $resource = $this->createResolvingResource(
            $route,
            [$preInterceptor, 'array_map']
        );
        $this->routing->preInterceptOnGet($preInterceptor);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function hasNoGlobalPostInterceptorsForDifferentMethod(): void
    {
        $postInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource($route);
        $this->routing->postInterceptOnHead($postInterceptor)
            ->postInterceptOnPost($postInterceptor)
            ->postInterceptOnPut($postInterceptor)
            ->postInterceptOnDelete($postInterceptor);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function hasGlobalPostInterceptorsEvenWhenNoRouteSelected(): void
    {
        $postInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource(
            $route,
            [],
            ['array_map', $postInterceptor]
        );
        $this->routing->postIntercept('array_map')
            ->postInterceptOnGet($postInterceptor);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    /**
     * @since  3.4.0
     */
    #[Test]
    public function hasGlobalPostInterceptorsWithMatchingPath(): void
    {
        $postInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource(
            $route,
            [],
            ['array_map']
        );
        $this->routing->postIntercept('array_map', '/hello')
            ->postInterceptOnGet($postInterceptor, '/world');
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function hasGlobalPostInterceptorsEvenWhenRouteSelected(): void
    {
        $postInterceptor = function() {};
        $postFunction    = 'array_map';
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {});
        $resource = $this->createResolvingResource(
            $route,
            [],
            [$postInterceptor, $postFunction]
        );
        $this->routing->postIntercept($postInterceptor)
            ->postIntercept($postFunction);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function mergesGlobalAndRoutePostInterceptors(): void
    {
        $postInterceptor = function() {};
        /** @var  Route  $route */
        $route = $this->routing->onGet('/hello', function() {})
            ->postIntercept('array_map');
        $resource = $this->createResolvingResource(
            $route,
            [],
            ['array_map', $postInterceptor]
        );
        $this->routing->postInterceptOnGet($postInterceptor);
        assertThat(
            $this->routing->findResource($this->calledUri),
            equals($resource)
        );
    }

    #[Test]
    public function supportsNoMimeTypeByDefault(): void
    {
        assertEmptyArray(
            $this->routing->findResource($this->calledUri)->supportedMimeTypes()
        );
    }

    /**
     * @since  5.0.0
     */
    #[Test]
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException(): void
    {
        expect(function() { $this->routing->supportsMimeType('application/foo'); })
            ->throws(\InvalidArgumentException::class);
    }

    #[Test]
    public function supportsGlobalAndRouteMimeTypesWhenRouteFound(): void
    {
        $this->routing->onGet('/hello', function() {})
            ->supportsMimeType('application/json');
        $this->routing->supportsMimeType('application/xml');
        assertThat(
            $this->routing->findResource($this->calledUri)->supportedMimeTypes(),
            equals(['application/json', 'application/xml'])
        );
    }

    /**
     * @since 5.0.0
     */
    #[Test]
    public function passesGlobalClassToSupportedMimeTypesOfSelectedRoute(): void
    {
        $request = NewInstance::of(Request::class)->returns([
            'readHeader' => ValueReader::forValue('application/foo')
        ]);
        $mimeType = new Json();
        $this->injector->returns(['getInstance' => $mimeType]);
        $this->routing->onGet('/hello', function() {})
            ->supportsMimeType('application/json');
        /** @var  class-string<\stubbles\webapp\response\mimetypes\MimeType>  $exampleClass */
        $exampleClass = 'example\Special';
        $this->routing->supportsMimeType('application/foo', $exampleClass);
        $response = NewInstance::stub(WebResponse::class);
        assertTrue(
            $this->routing->findResource($this->calledUri)
                ->negotiateMimeType($request, $response)
        );
        verify($response, 'adjustMimeType')->received($mimeType);
    }

    /**
     * @since 5.1.0
     */
    #[Test]
    #[Group('issue_72')]
    public function doesNotEnableMimeTypeForDefaultClassWhenRouteDoesNotSupportMimeType(): void
    {
        /** @var  class-string<\stubbles\webapp\response\mimetypes\MimeType>  $exampleClass */
        $exampleClass = 'example\Special';
        $this->routing->setDefaultMimeTypeClass('application/foo', $exampleClass);
        $this->routing->onGet('/hello', function() {});
        assertThat(
            $this->routing->findResource($this->calledUri)->supportedMimeTypes(),
            doesNotContain('application/foo')
        );
    }

    /**
     * @since 5.1.1
     */
    #[Test]
    #[Group('issue_72')]
    public function passesDefaultClassToSupportedMimeTypesOfSelectedRouteWhenRouteSupportsMimeType(): void
    {
        /** @var  class-string<\stubbles\webapp\response\mimetypes\MimeType>  $exampleClass */
        $exampleClass = 'example\Special';
        $this->routing->setDefaultMimeTypeClass('application/foo', $exampleClass);
        $this->routing->onGet('/hello', function() {})->supportsMimeType('application/foo');
        assertThat(
            $this->routing->findResource($this->calledUri)->supportedMimeTypes(),
            contains('application/foo')
        );
    }

    /**
     * @since  2.1.1
     */
    #[Test]
    public function contentNegotationCanBeDisabled(): void
    {
        $this->routing->onGet('/hello', function() {});
        $response = NewInstance::stub(WebResponse::class);
        $this->routing->disableContentNegotiation();
        assertTrue(
            $this->routing->findResource($this->calledUri)
                ->negotiateMimeType(
                    NewInstance::of(Request::class),
                    $response
                )
        );
        verify($response, 'adjustMimeType')->wasNeverCalled();
    }

    /**
     * @since  2.1.1
     */
    #[Test]
    public function contentNegotationIsDisabledWhenDisabledForRoute(): void
    {
        $this->routing->onGet('/hello', function() {})
                      ->disableContentNegotiation();
        $response = NewInstance::stub(WebResponse::class);
        $this->routing->disableContentNegotiation();
        assertTrue(
            $this->routing->findResource($this->calledUri)
                ->negotiateMimeType(
                    NewInstance::of(Request::class),
                    $response
                )
        );
        verify($response, 'adjustMimeType')->wasNeverCalled();
    }

    public static function provideCalledHtmlUris(): Generator
    {
        yield ['index.html'];
        yield ['foo_BAR-123.html'];
        yield ['123.html'];
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    #[DataProvider('provideCalledHtmlUris')]
    public function passThroughOnGetAppliesForHtmlFilesWithDefaultPath(string $htmlFile): void
    {
        /** @var  Route  $route */
        $route = $this->routing->passThroughOnGet();
        $expected = $this->createResolvingResource($route, [], [], $htmlFile);
        assertThat(
            $this->routing->findResource('http://example.net/' . $htmlFile, 'GET'),
            equals($expected)
        );
    }

    /**
     * @since  6.1.0
     */
    #[Test]
    public function apiIndexOnGetCreatesRouteWithIndexTarget(): void
    {
        /** @var  Route  $route */
        $route = $this->routing->apiIndexOnGet('/');
        assertThat($route->target(), isInstanceOf(Index::class));
    }

    /**
     * @since  6.1.0
     */
    #[Test]
    public function redirectOnGetCreatesRouteWithRedirectTarget(): void
    {
        /** @var  Route  $route */
        $route = $this->routing->redirectOnGet('/foo', '/bar');
        assertThat($route->target(), isInstanceOf(Redirect::class));
    }
}

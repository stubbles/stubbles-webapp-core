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
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use stubbles\input\ValueReader;
use stubbles\ioc\Injector;
use stubbles\webapp\{Request, Response};
use stubbles\webapp\auth\ProtectedResource;
use stubbles\webapp\response\WebResponse;
use stubbles\webapp\response\mimetypes\Json;
use stubbles\webapp\routing\api\Index;

use function bovigo\assert\{
    assert,
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
 * @group  routing
 */
class RoutingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\Routing
     */
    private $routing;
    /**
     * @type  \bovigo\callmap\Proxy
     */
    private $injector;
    /**
     * called uri during tests
     *
     * @type  \stubbles\webapp\routing\CalledUri
     */
    private $calledUri;

    /**
     * set up test environment
     */
    public function setUp()
    {
        SupportedMimeTypes::removeDefaultMimeTypeClass('application/foo');
        $this->injector  = NewInstance::stub(Injector::class);
        $this->routing   = new Routing($this->injector);
        $this->calledUri = new CalledUri('http://example.net/hello', 'GET');
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        SupportedMimeTypes::removeDefaultMimeTypeClass('application/foo');
    }

    /**
     * @test
     */
    public function returnsNotFoundOnRouteSelectionWhenNoRouteAdded()
    {
        assert(
                $this->routing->findResource($this->calledUri),
                isInstanceOf(NotFound::class)
        );
    }

    /**
     * @test
     */
    public function returnsNotFoundOnRouteSelectionWhenNoSuitableRouteAdded()
    {
        $this->routing->onHead('/bar', function() {});
        $this->routing->onGet('/foo', function() {});
        assert(
                $this->routing->findResource($this->calledUri),
                isInstanceOf(NotFound::class)
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsResourceOptionsOnRouteSelectionWhenNoSuitableRouteForMethodAddedButIsOptionsRequest()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        assert(
                $this->routing->findResource('http://example.net/hello', 'OPTIONS'),
                isInstanceOf(ResourceOptions::class)
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsMethodNotAllowedOnRouteSelectionWhenNoSuitableRouteForMethodAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        assert(
                $this->routing->findResource($this->calledUri),
                isInstanceOf(MethodNotAllowed::class)
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function returnsProtectedResourceWhenMatchingRouteRequiresLogin()
    {
        $this->routing->onGet('/hello', function() {})->withLoginOnly();
        assert(
                $this->routing->findResource($this->calledUri),
                isInstanceOf(ProtectedResource::class)
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function returnsProtectedResourceWhenMatchingRouteRequiresRole()
    {
        $this->routing->onGet('/hello', function() {})->withRoleOnly('admin');
        assert(
                $this->routing->findResource($this->calledUri),
                isInstanceOf(ProtectedResource::class)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function routeWithoutMethodRestrictionReturnsResourceOptionsOnOptionRequest()
    {
        $this->routing->onAll('/hello', function() { });
        assert(
                $this->routing->findResource('http://example.net/hello', 'OPTIONS'),
                isInstanceOf(ResourceOptions::class)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function routeWithoutMethodRestrictionProvidesListOfAllMethodsOnOptionRequest()
    {
        $this->routing->onAll('/hello', function() { });
        $response = NewInstance::of(Response::class);
        $this->routing->findResource('http://example.net/hello', 'OPTIONS')
                ->resolve(NewInstance::of(Request::class), $response);
        verify($response, 'addHeader')
                ->received('Allow', 'GET, HEAD, POST, PUT, DELETE, OPTIONS');
    }

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

    /**
     * @test
     */
    public function returnsRouteWhichFitsMethodAndPath()
    {
        $route = $this->createResolvingResource($this->routing->onGet('/hello', function() {}));
        assert($this->routing->findResource($this->calledUri), equals($route));
    }

    /**
     * @test
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException()
    {
        expect(function() { $this->routing->preIntercept(303); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function hasNoGlobalPreInterceptorsForDifferentMethod()
    {
        $preInterceptor = function() {};
        $route = $this->createResolvingResource($this->routing->onGet('/hello', function() {}));
        assert(
                $this->routing->preInterceptOnHead($preInterceptor)
                        ->preInterceptOnPost($preInterceptor)
                        ->preInterceptOnPut($preInterceptor)
                        ->preInterceptOnDelete($preInterceptor)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenNoRouteSelected()
    {
        $preInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {}),
                ['array_map', $preInterceptor]
        );
        assert(
                $this->routing->preIntercept('array_map')
                        ->preInterceptOnGet($preInterceptor)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     * @since  3.4.0
     */
    public function hasGlobalPreInterceptorsWithMatchingPath()
    {
        $preInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {}),
                ['array_map']
        );
        assert(
                $this->routing->preIntercept('array_map', '/hello')
                        ->preInterceptOnGet($preInterceptor, '/world')
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenRouteSelected()
    {
        $preInterceptor = function() {};
        $preFunction    = 'array_map';
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {}),
                [$preInterceptor, $preFunction]
        );
        assert(
                $this->routing->preIntercept($preInterceptor)
                        ->preIntercept($preFunction)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePreInterceptors()
    {
        $preInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {})
                        ->preIntercept('array_map'),
                [$preInterceptor, 'array_map']
        );
        assert(
                $this->routing->preInterceptOnGet($preInterceptor)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException()
    {
        expect(function() { $this->routing->postIntercept(303); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function hasNoGlobalPostInterceptorsForDifferentMethod()
    {
        $postInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {})
        );
        assert(
                $this->routing->postInterceptOnHead($postInterceptor)
                        ->postInterceptOnPost($postInterceptor)
                        ->postInterceptOnPut($postInterceptor)
                        ->postInterceptOnDelete($postInterceptor)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenNoRouteSelected()
    {
        $postInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {}),
                [],
                ['array_map', $postInterceptor]
        );
        assert(
                $this->routing->postIntercept('array_map')
                        ->postInterceptOnGet($postInterceptor)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     * @since  3.4.0
     */
    public function hasGlobalPostInterceptorsWithMatchingPath()
    {
        $postInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {}),
                [],
                ['array_map']
        );
        assert(
                $this->routing->postIntercept('array_map', '/hello')
                        ->postInterceptOnGet($postInterceptor, '/world')
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenRouteSelected()
    {
        $postInterceptor = function() {};
        $postFunction    = 'array_map';
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {}),
                [],
                [$postInterceptor,
                 $postFunction
                ]
        );
        assert(
                $this->routing->postIntercept($postInterceptor)
                        ->postIntercept($postFunction)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePostInterceptors()
    {
        $postInterceptor = function() {};
        $route = $this->createResolvingResource(
                $this->routing->onGet('/hello', function() {})
                              ->postIntercept('array_map'),
                [],
                ['array_map', $postInterceptor]
        );
        assert(
                $this->routing->postInterceptOnGet($postInterceptor)
                        ->findResource($this->calledUri),
                equals($route)
        );
    }

    /**
     * @test
     */
    public function supportsNoMimeTypeByDefault()
    {
        assertEmptyArray(
                $this->routing->findResource($this->calledUri)
                        ->supportedMimeTypes()
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException()
    {
        expect(function() { $this->routing->supportsMimeType('application/foo'); })
                ->throws(\InvalidArgumentException::class);
    }

    /**
     * @test
     */
    public function supportsGlobalAndRouteMimeTypesWhenRouteFound()
    {
        $this->routing->onGet('/hello', function() {})
                    ->supportsMimeType('application/json');
        assert(
                $this->routing->supportsMimeType('application/xml')
                        ->findResource($this->calledUri)
                        ->supportedMimeTypes(),
                equals(['application/json', 'application/xml'])
        );
    }

    /**
     * @since 5.0.0
     * @test
     */
    public function passesGlobalClassToSupportedMimeTypesOfSelectedRoute()
    {
        $request = NewInstance::of(Request::class)->mapCalls([
                'readHeader' => ValueReader::forValue('application/foo')
        ]);
        $mimeType = new Json();
        $this->injector->mapCalls(['getInstance' => $mimeType]);
        $this->routing->onGet('/hello', function() {})
                ->supportsMimeType('application/json');
        $this->routing->supportsMimeType('application/foo', 'example\Special');
        $response = NewInstance::stub(WebResponse::class);
        assertTrue(
                $this->routing->findResource($this->calledUri)
                        ->negotiateMimeType($request, $response)
        );
        verify($response, 'adjustMimeType')->received($mimeType);
    }

    /**
     * @since 5.1.0
     * @test
     * @group  issue_72
     */
    public function doesNotEnableMimeTypeForDefaultClassWhenRouteDoesNotSupportMimeType()
    {
        $this->routing->setDefaultMimeTypeClass('application/foo', 'example\Special');
        $this->routing->onGet('/hello', function() {});
        assert(
                $this->routing->findResource($this->calledUri)
                        ->supportedMimeTypes(),
                doesNotContain('application/foo')
        );
    }

    /**
     * @since 5.1.1
     * @test
     * @group  issue_72
     */
    public function passesDefaultClassToSupportedMimeTypesOfSelectedRouteWhenRouteSupportsMimeType()
    {
        $this->routing->setDefaultMimeTypeClass('application/foo', 'example\Special');
        $this->routing->onGet('/hello', function() {})->supportsMimeType('application/foo');
        assert(
                $this->routing->findResource($this->calledUri)
                        ->supportedMimeTypes(),
                contains('application/foo')
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        $this->routing->onGet('/hello', function() {});
        $response = NewInstance::stub(WebResponse::class);
        assertTrue(
                $this->routing->disableContentNegotiation()
                        ->findResource($this->calledUri)
                        ->negotiateMimeType(
                                NewInstance::of(Request::class),
                                $response
                        )
        );
        verify($response, 'adjustMimeType')->wasNeverCalled();
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsDisabledWhenDisabledForRoute()
    {
        $this->routing->onGet('/hello', function() {})
                      ->disableContentNegotiation();
        $response = NewInstance::stub(WebResponse::class);
        assertTrue(
                $this->routing->disableContentNegotiation()
                        ->findResource($this->calledUri)
                        ->negotiateMimeType(
                                NewInstance::of(Request::class),
                                $response
                        )
        );
        verify($response, 'adjustMimeType')->wasNeverCalled();
    }

    public function calledHtmlUris(): array
    {
        return [
            ['index.html'],
            ['foo_BAR-123.html'],
            ['123.html']
        ];
    }

    /**
     * @test
     * @dataProvider  calledHtmlUris
     * @since  4.0.0
     */
    public function passThroughOnGetAppliesForHtmlFilesWithDefaultPath(string $htmlFile)
    {
        $expected = $this->createResolvingResource(
                $this->routing->passThroughOnGet(),
                [],
                [],
                $htmlFile
        );
        assert(
                $this->routing->findResource('http://example.net/' . $htmlFile, 'GET'),
                equals($expected)
        );
    }

    /**
     * @test
     * @since  6.1.0
     */
    public function apiIndexOnGetCreatesRouteWithIndexTarget()
    {
        assert(
                $this->routing->apiIndexOnGet('/')->target(),
                isInstanceOf(Index::class)
        );

    }

    /**
     * @test
     * @since  6.1.0
     */
    public function redirectOnGetCreatesRouteWithRedirectTarget()
    {
        assert(
                $this->routing->redirectOnGet('/foo', '/bar')->target(),
                isInstanceOf(Redirect::class)
        );

    }
}

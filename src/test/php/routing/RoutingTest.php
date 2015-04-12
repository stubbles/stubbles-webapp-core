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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\input\ValueReader;
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
        $this->injector  = NewInstance::stub('stubbles\ioc\Injector');
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
        assertInstanceOf(
                'stubbles\webapp\routing\NotFound',
                $this->routing->findResource($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function returnsNotFoundOnRouteSelectionWhenNoSuitableRouteAdded()
    {
        $this->routing->onHead('/bar', function() {});
        $this->routing->onGet('/foo', function() {});
        assertInstanceOf(
                'stubbles\webapp\routing\NotFound',
                $this->routing->findResource($this->calledUri)
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
        assertInstanceOf(
                'stubbles\webapp\routing\ResourceOptions',
                $this->routing->findResource('http://example.net/hello', 'OPTIONS')
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
        assertInstanceOf(
                'stubbles\webapp\routing\MethodNotAllowed',
                $this->routing->findResource($this->calledUri)
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function returnsProtectedResourceWhenMatchingRouteRequiresLogin()
    {
        $this->routing->onGet('/hello', function() {})->withLoginOnly();
        assertInstanceOf(
                'stubbles\webapp\auth\ProtectedResource',
                $this->routing->findResource($this->calledUri)
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function returnsProtectedResourceWhenMatchingRouteRequiresRole()
    {
        $this->routing->onGet('/hello', function() {})->withRoleOnly('admin');
        assertInstanceOf(
                'stubbles\webapp\auth\ProtectedResource',
                $this->routing->findResource($this->calledUri)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function routeWithoutMethodRestrictionReturnsResourceOptionsOnOptionRequest()
    {
        $this->routing->onAll('/hello', function() { });
        assertInstanceOf(
                'stubbles\webapp\routing\ResourceOptions',
                $this->routing->findResource('http://example.net/hello', 'OPTIONS')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function routeWithoutMethodRestrictionProvidesListOfAllMethodsOnOptionRequest()
    {
        $this->routing->onAll('/hello', function() { });
        $response = NewInstance::of('stubbles\webapp\Response');
        $this->routing->findResource('http://example.net/hello', 'OPTIONS')
                ->resolve(NewInstance::of('stubbles\webapp\Request'), $response);
        callmap\verify($response, 'addHeader')
                ->received('Allow', 'GET, HEAD, POST, PUT, DELETE, OPTIONS');
    }

    /**
     * creates processable route with given list of pre and post interceptors
     *
     * @param   Route   $route
     * @param   array   $preInterceptors
     * @param   array   $postInterceptors
     * @param   string  $path
     * @return  \stubbles\webapp\routing\ResolvingResource
     */
    private function createResolvingResource(
            Route $route,
            array $preInterceptors = [],
            array $postInterceptors = [],
            $path = 'hello')
    {
        $injector = NewInstance::stub('stubbles\ioc\Injector');
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
        assertEquals($route, $this->routing->findResource($this->calledUri));
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function addInvalidPreInterceptorThrowsIllegalArgumentException()
    {
        $this->routing->preIntercept(303);
    }

    /**
     * @test
     */
    public function hasNoGlobalPreInterceptorsForDifferentMethod()
    {
        $preInterceptor = function() {};
        $route = $this->createResolvingResource($this->routing->onGet('/hello', function() {}));
        assertEquals(
                $route,
                $this->routing->preInterceptOnHead($preInterceptor)
                        ->preInterceptOnPost($preInterceptor)
                        ->preInterceptOnPut($preInterceptor)
                        ->preInterceptOnDelete($preInterceptor)
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->preIntercept('array_map')
                        ->preInterceptOnGet($preInterceptor)
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->preIntercept('array_map', '/hello')
                        ->preInterceptOnGet($preInterceptor, '/world')
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->preIntercept($preInterceptor)
                        ->preIntercept($preFunction)
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->preInterceptOnGet($preInterceptor)
                        ->findResource($this->calledUri)
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function addInvalidPostInterceptorThrowsIllegalArgumentException()
    {
        $this->routing->postIntercept(303);
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
        assertEquals(
                $route,
                $this->routing->postInterceptOnHead($postInterceptor)
                        ->postInterceptOnPost($postInterceptor)
                        ->postInterceptOnPut($postInterceptor)
                        ->postInterceptOnDelete($postInterceptor)
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->postIntercept('array_map')
                        ->postInterceptOnGet($postInterceptor)
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->postIntercept('array_map', '/hello')
                        ->postInterceptOnGet($postInterceptor, '/world')
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->postIntercept($postInterceptor)
                        ->postIntercept($postFunction)
                        ->findResource($this->calledUri)
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
        assertEquals(
                $route,
                $this->routing->postInterceptOnGet($postInterceptor)
                        ->findResource($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function supportsNoMimeTypeByDefault()
    {
        assertEquals(
                [],
                $this->routing->findResource($this->calledUri)
                        ->supportedMimeTypes()
        );
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @since  5.0.0
     */
    public function addMimeTypeWithoutClassWhenNoDefaultClassIsKnownThrowsInvalidArgumentException()
    {
        $this->routing->supportsMimeType('application/foo');
    }

    /**
     * @test
     */
    public function supportsGlobalAndRouteMimeTypesWhenRouteFound()
    {
        $this->routing->onGet('/hello', function() {})
                    ->supportsMimeType('application/json');
        assertEquals(
                ['application/json',
                 'application/xml'
                ],
                $this->routing->supportsMimeType('application/xml')
                        ->findResource($this->calledUri)
                        ->supportedMimeTypes()
        );
    }

    /**
     * @since 5.0.0
     * @test
     */
    public function passesGlobalClassToSupportedMimeTypesOfSelectedRoute()
    {
        $request = NewInstance::of('stubbles\webapp\Request')
                ->mapCalls(['readHeader' => ValueReader::forValue('application/foo')]);
        $mimeType = new \stubbles\webapp\response\mimetypes\Json();
        $this->injector->mapCalls(['getInstance' => $mimeType]);
        $this->routing->onGet('/hello', function() {})
                ->supportsMimeType('application/json');
        $this->routing->supportsMimeType('application/foo', 'example\Special');
        $response = NewInstance::stub('stubbles\webapp\response\WebResponse');
        assertTrue(
                $this->routing->findResource($this->calledUri)
                        ->negotiateMimeType($request, $response)
        );
        callmap\verify($response, 'adjustMimeType')->received($mimeType);
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
        assertNotContains(
                'application/foo',
                $this->routing->findResource($this->calledUri)
                              ->supportedMimeTypes()
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
        assertContains(
                'application/foo',
                $this->routing->findResource($this->calledUri)
                              ->supportedMimeTypes()
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        $this->routing->onGet('/hello', function() {});
        $response = NewInstance::stub('stubbles\webapp\response\WebResponse');
        assertTrue(
                $this->routing->disableContentNegotiation()
                        ->findResource($this->calledUri)
                        ->negotiateMimeType(
                                NewInstance::of('stubbles\webapp\Request'),
                                $response
                        )
        );
        callmap\verify($response, 'adjustMimeType')->wasNeverCalled();
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsDisabledWhenDisabledForRoute()
    {
        $this->routing->onGet('/hello', function() {})
                      ->disableContentNegotiation();
        $response = NewInstance::stub('stubbles\webapp\response\WebResponse');
        assertTrue(
                $this->routing->disableContentNegotiation()
                        ->findResource($this->calledUri)
                        ->negotiateMimeType(
                                NewInstance::of('stubbles\webapp\Request'),
                                $response
                        )
        );
        callmap\verify($response, 'adjustMimeType')->wasNeverCalled();
    }

    /**
     * @return  array
     */
    public function calledHtmlUris()
    {
        return [
            ['index.html'],
            ['foo_BAR-123.html'],
            ['123.html']
        ];
    }

    /**
     * @param  string  $htmlFile
     * @test
     * @dataProvider  calledHtmlUris
     * @since  4.0.0
     */
    public function passThroughOnGetAppliesForHtmlFilesWithDefaultPath($htmlFile)
    {
        assertEquals(
                $this->createResolvingResource($this->routing->passThroughOnGet(), [], [], $htmlFile),
                $this->routing->findResource('http://example.net/' . $htmlFile, 'GET')
        );
    }
}

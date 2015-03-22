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
use stubbles\input\ValueReader;
use stubbles\lang\reflect;
use stubbles\webapp\UriRequest;
use stubbles\webapp\interceptor\Interceptors;
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
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;
    /**
     * called uri during tests
     *
     * @type  UriRequest
     */
    private $calledUri;

    /**
     * set up test environment
     */
    public function setUp()
    {
        SupportedMimeTypes::removeDefaultMimeTypeClass('application/foo');
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->routing   = new Routing($this->mockInjector);
        $this->calledUri = new UriRequest('http://example.net/hello', 'GET');
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
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(
                reflect\annotationsOfConstructor($this->routing)
                        ->contain('Inject')
        );
    }

    /**
     * @test
     */
    public function returnsMissingRouteOnRouteSelectionWhenNoRouteAdded()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\routing\MissingRoute',
                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function returnsMissingRouteOnRouteSelectionWhenNoSuitableRouteAdded()
    {
        $this->routing->onHead('/bar', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertInstanceOf(
                'stubbles\webapp\routing\MissingRoute',
                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsOptionsRouteOnRouteSelectionWhenNoSuitableRouteForMethodAddedButIsOptionsRequest()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertInstanceOf(
                'stubbles\webapp\routing\OptionsRoute',
                $this->routing->findRoute('http://example.net/hello', 'OPTIONS')
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsMethodNotAllowedRouteOnRouteSelectionWhenNoSuitableRouteForMethodAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertInstanceOf(
                'stubbles\webapp\routing\MethodNotAllowedRoute',
                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function returnsAuthorizingRouteWhenMatchingRouteRequiresLogin()
    {
        $this->routing->onGet('/hello', function() {})->withLoginOnly();
        $this->assertInstanceOf(
                'stubbles\webapp\auth\AuthorizingRoute',
                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function returnsAuthorizingRouteWhenMatchingRouteRequiresRole()
    {
        $this->routing->onGet('/hello', function() {})->withRoleOnly('admin');
        $this->assertInstanceOf(
                'stubbles\webapp\auth\AuthorizingRoute',
                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function routeWithoutMethodRestrictionReturnsOptionRouteOnOptionRequest()
    {
        $this->routing->onAll('/hello', function() { });
        $this->assertInstanceOf(
                'stubbles\webapp\routing\OptionsRoute',
                $this->routing->findRoute('http://example.net/hello', 'OPTIONS')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function routeWithoutMethodRestrictionProvidesListOfAllMethodsOnOptionRequest()
    {
        $this->routing->onAll('/hello', function() { });
        $mockResponse = $this->getMock('stubbles\webapp\Response');
        $mockResponse->expects($this->at(0))
                     ->method('addHeader')
                     ->with($this->equalTo('Allow'), $this->equalTo('GET, HEAD, POST, PUT, DELETE, OPTIONS'))
                     ->will($this->returnSelf());
        $this->routing->findRoute('http://example.net/hello', 'OPTIONS')
                      ->process($this->getMock('stubbles\webapp\Request'), $mockResponse);
    }

    /**
     * creates processable route with given list of pre and post interceptors
     *
     * @param   Route   $route
     * @param   array   $preInterceptors
     * @param   array   $postInterceptors
     * @param   string  $path
     * @return  ProcessableRoute
     */
    private function createProcessableRoute(Route $route, array $preInterceptors = [], array $postInterceptors = [], $path = 'hello')
    {
        $mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                             ->disableOriginalConstructor()
                             ->getMock();
        return new MatchingRoute(
                $mockInjector,
                new UriRequest('http://example.net/' . $path, 'GET'),
                new Interceptors($mockInjector, $preInterceptors, $postInterceptors),
                new SupportedMimeTypes([]),
                $route
        );
    }

    /**
     * @test
     */
    public function returnsRouteWhichFitsMethodAndPath()
    {
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}));
        $this->assertEquals($route, $this->routing->findRoute($this->calledUri));
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}));
        $this->assertEquals(
                $route,
                $this->routing->preInterceptOnHead($preInterceptor)
                              ->preInterceptOnPost($preInterceptor)
                              ->preInterceptOnPut($preInterceptor)
                              ->preInterceptOnDelete($preInterceptor)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenNoRouteSelected()
    {
        $preInterceptor = function() {};
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}),
                                               ['array_map', $preInterceptor]
        );
        $this->assertEquals(
                $route,
                $this->routing->preIntercept('array_map')
                              ->preInterceptOnGet($preInterceptor)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  3.4.0
     */
    public function hasGlobalPreInterceptorsWithMatchingPath()
    {
        $preInterceptor = function() {};
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}),
                                               ['array_map']
        );
        $this->assertEquals(
                $route,
                $this->routing->preIntercept('array_map', '/hello')
                              ->preInterceptOnGet($preInterceptor, '/world')
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenRouteSelected()
    {
        $preInterceptor     = function() {};
        $mockPreFunction    = 'array_map';
        $route = $this->createProcessableRoute(
                $this->routing->onGet('/hello', function() {}),
                [$preInterceptor,
                 $mockPreFunction
                ]
        );
        $this->assertEquals(
                $route,
                $this->routing->preIntercept($preInterceptor)
                              ->preIntercept($mockPreFunction)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePreInterceptors()
    {
        $preInterceptor = function() {};
        $route = $this->createProcessableRoute(
                $this->routing->onGet('/hello', function() {})
                              ->preIntercept('array_map'),
                [$preInterceptor, 'array_map']
        );
        $this->assertEquals(
                $route,
                $this->routing->preInterceptOnGet($preInterceptor)
                              ->findRoute($this->calledUri)
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}));
        $this->assertEquals(
                $route,
                $this->routing->postInterceptOnHead($postInterceptor)
                              ->postInterceptOnPost($postInterceptor)
                              ->postInterceptOnPut($postInterceptor)
                              ->postInterceptOnDelete($postInterceptor)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenNoRouteSelected()
    {
        $postInterceptor = function() {};
        $route = $this->createProcessableRoute(
                $this->routing->onGet('/hello', function() {}),
                [],
                ['array_map', $postInterceptor]
        );
        $this->assertEquals(
                $route,
                $this->routing->postIntercept('array_map')
                              ->postInterceptOnGet($postInterceptor)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  3.4.0
     */
    public function hasGlobalPostInterceptorsWithMatchingPath()
    {
        $postInterceptor = function() {};
        $route = $this->createProcessableRoute(
                $this->routing->onGet('/hello', function() {}),
                [],
                ['array_map']
        );
        $this->assertEquals(
                $route,
                $this->routing->postIntercept('array_map', '/hello')
                              ->postInterceptOnGet($postInterceptor, '/world')
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenRouteSelected()
    {
        $postInterceptor  = function() {};
        $mockPostFunction = 'array_map';
        $route = $this->createProcessableRoute(
                $this->routing->onGet('/hello', function() {}),
                [],
                [$postInterceptor,
                 $mockPostFunction
                ]
        );
        $this->assertEquals(
                $route,
                $this->routing->postIntercept($postInterceptor)
                              ->postIntercept($mockPostFunction)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePostInterceptors()
    {
        $postInterceptor = function() {};
        $route = $this->createProcessableRoute(
                $this->routing->onGet('/hello', function() {})
                              ->postIntercept('array_map'),
                [],
                ['array_map', $postInterceptor]
        );
        $this->assertEquals(
                $route,
                $this->routing->postInterceptOnGet($postInterceptor)
                              ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function supportsNoMimeTypeByDefault()
    {
        $this->assertEquals(
                [],
                $this->routing->findRoute($this->calledUri)
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
        $this->assertEquals(
                ['application/json',
                 'application/xml'
                ],
                $this->routing->supportsMimeType('application/xml')
                              ->findRoute($this->calledUri)
                              ->supportedMimeTypes()
        );
    }

    /**
     * @since 5.0.0
     * @test
     */
    public function passesGlobalClassToSupportedMimeTypesOfSelectedRoute()
    {
        $mockRequest = $this->getMock('stubbles\webapp\Request');
        $mockRequest->expects($this->once())
                ->method('readHeader')
                ->with($this->equalTo('HTTP_ACCEPT'))
                ->will($this->returnValue(ValueReader::forValue('application/foo')));
        $mockMimeType = new \stubbles\webapp\response\mimetypes\Json();
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('example\Special'))
                           ->will($this->returnValue($mockMimeType));
        $this->routing->onGet('/hello', function() {})
                      ->supportsMimeType('application/json');
        $this->routing->supportsMimeType('application/foo', 'example\Special');
        $this->assertSame(
                $mockMimeType,
                $this->routing->findRoute($this->calledUri)
                              ->negotiateMimeType($mockRequest)
        );
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
        $this->assertNotContains(
                'application/foo',
                $this->routing->findRoute($this->calledUri)
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
        $this->assertContains(
                'application/foo',
                $this->routing->findRoute($this->calledUri)
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
       $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $this->routing->disableContentNegotiation()
                              ->findRoute($this->calledUri)
                              ->negotiateMimeType($this->getMock('stubbles\webapp\Request'))
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsDisabledWhenDisabledForRoute()
    {
        $this->routing->onGet('/hello', function() {})
                      ->disableContentNegotiation();
        $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $this->routing->disableContentNegotiation()
                              ->findRoute($this->calledUri)
                              ->negotiateMimeType($this->getMock('stubbles\webapp\Request'))
        );
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
        $this->assertEquals(
                $this->createProcessableRoute($this->routing->passThroughOnGet(), [], [], $htmlFile),
                $this->routing->findRoute('http://example.net/' . $htmlFile, 'GET')
        );
    }
}

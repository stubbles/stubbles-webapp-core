<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\lang;
/**
 * Tests for net\stubbles\webapp\Routing.
 *
 * @since  2.0.0
 * @group  core
 */
class RoutingTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  Routing
     */
    private $routing;
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
        $this->routing   = new Routing($this->getMockBuilder('net\stubbles\ioc\Injector')
                                            ->disableOriginalConstructor()
                                            ->getMock()
                           );
        $this->calledUri = UriRequest::fromString('http://example.net/hello', 'GET');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->routing)
                              ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function annotationPresentOnSetAuthHandlerMethod()
    {
        $method = lang\reflect($this->routing, 'setAuthHandler');
        $this->assertTrue($method->hasAnnotation('Inject'));
        $this->assertTrue($method->getAnnotation('Inject')->isOptional());
    }

    /**
     * @test
     */
    public function returnsMissingRouteOnRouteSelectionWhenNoRouteAdded()
    {
        $this->assertInstanceOf('net\stubbles\webapp\MissingRoute',
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
        $this->assertInstanceOf('net\stubbles\webapp\MissingRoute',
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
        $this->assertInstanceOf('net\stubbles\webapp\OptionsRoute',
                                $this->routing->findRoute(UriRequest::fromString('http://example.net/hello', 'OPTIONS'))
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
        $this->assertInstanceOf('net\stubbles\webapp\MethodNotAllowedRoute',
                                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function returnsInternalServerErrorRouteWhenMatchingRouteRequiresAuthButNoAuthHandlerSet()
    {
        $this->routing->onGet('/hello', function() {})->withRoleOnly('admin');
        $this->assertInstanceOf('net\stubbles\webapp\InternalServerErrorRoute',
                                $this->routing->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @since  3.0.0
     */
    public function returnsAuthorizingRouteWhenMatchingRouteRequiresAuthButNoAuthHandlerSet()
    {
        $this->routing->onGet('/hello', function() {})->withRoleOnly('admin');
        $this->assertInstanceOf('net\stubbles\webapp\auth\AuthorizingRoute',
                                $this->routing->setAuthHandler($this->getMock('net\stubbles\webapp\auth\AuthHandler'))
                                              ->findRoute($this->calledUri)
        );
    }

    /**
     * creates processable route with given list of pre and post interceptors
     *
     * @param   array  $preInterceptors
     * @param   array  $postInterceptors
     * @return  ProcessableRoute
     */
    private function createProcessableRoute(Route $route, array $preInterceptors = array(), array $postInterceptors = array())
    {
        $mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                             ->disableOriginalConstructor()
                             ->getMock();
        return new MatchingRoute(UriRequest::fromString('http://example.net/hello', 'GET'),
                                 new interceptor\Interceptors($mockInjector, $preInterceptors, $postInterceptors),
                                 new response\SupportedMimeTypes(array()),
                                 $route,
                                 $mockInjector
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
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
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
        $this->assertEquals($route,
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
                                               array('array_map', $preInterceptor)
        );
        $this->assertEquals($route,
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
        $this->assertEquals($route,
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}),
                                               array($preInterceptor,
                                                     $mockPreFunction
                                               )
                 );
        $this->assertEquals($route,
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {})
                                                             ->preIntercept('array_map'),
                                               array($preInterceptor, 'array_map')
                 );
        $this->assertEquals($route,
                            $this->routing->preInterceptOnGet($preInterceptor)
                                          ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
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
        $this->assertEquals($route,
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}),
                                               array(),
                                               array('array_map', $postInterceptor)
                 );
        $this->assertEquals($route,
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}),
                                               [],
                                               ['array_map']
                 );
        $this->assertEquals($route,
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {}),
                                               array(),
                                               array($postInterceptor,
                                                     $mockPostFunction
                                               )
                 );
        $this->assertEquals($route,
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
        $route = $this->createProcessableRoute($this->routing->onGet('/hello', function() {})
                                                             ->postIntercept('array_map'),
                                               array(),
                                               array('array_map', $postInterceptor)
                 );
        $this->assertEquals($route,
                            $this->routing->postInterceptOnGet($postInterceptor)
                                          ->findRoute($this->calledUri)
        );
    }

    /**
     * @test
     */
    public function supportsNoMimeTypeByDefault()
    {
        $this->assertEquals(array(),
                            $this->routing->findRoute($this->calledUri)
                                          ->getSupportedMimeTypes()
                                          ->asArray()
        );
    }

    /**
     * @test
     */
    public function supportsGlobalAndRouteMimeTypesWhenRouteFound()
    {
        $this->routing->onGet('/hello', function() {})
                      ->supportsMimeType('application/json');
        $this->assertEquals(array('application/json',
                                  'application/xml'
                            ),
                            $this->routing->supportsMimeType('application/xml')
                                          ->findRoute($this->calledUri)
                                          ->getSupportedMimeTypes()
                                          ->asArray()
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationIsEnabledByDefault()
    {
        $this->routing->onGet('/hello', function() {});
        $this->assertFalse($this->routing->findRoute($this->calledUri)
                                         ->getSupportedMimeTypes()
                                         ->isContentNegotationDisabled()
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function contentNegotationCanBeDisabled()
    {
        $this->routing->onGet('/hello', function() {});
        $this->assertTrue($this->routing->disableContentNegotiation()
                                        ->findRoute($this->calledUri)
                                        ->getSupportedMimeTypes()
                                        ->isContentNegotationDisabled()
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
        $this->assertTrue($this->routing->disableContentNegotiation()
                                        ->findRoute($this->calledUri)
                                        ->getSupportedMimeTypes()
                                        ->isContentNegotationDisabled()
        );
    }
}

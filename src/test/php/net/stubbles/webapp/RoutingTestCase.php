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
     * set up test environment
     */
    public function setUp()
    {
        $this->routing = new Routing(UriRequest::fromString('http://example.net/hello', 'GET'),
                                     $this->getMockBuilder('net\stubbles\ioc\Injector')
                                          ->disableOriginalConstructor()
                                          ->getMock()
                         );
    }

    /**
     * @test
     */
    public function returnsMissingRouteOnRouteSelectionWhenNoRouteAdded()
    {
        $this->assertInstanceOf('net\stubbles\webapp\MissingRoute',
                                $this->routing->findRoute()
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
                                $this->routing->findRoute()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsOptionsRouteOnRouteSelectionWhenNoSuitableRouteForMethodAddedButIsOptionsRequest()
    {
        $routing = new Routing(UriRequest::fromString('http://example.net/hello', 'OPTIONS'),
                               $this->getMockBuilder('net\stubbles\ioc\Injector')
                                    ->disableOriginalConstructor()
                                    ->getMock()
                   );
        $routing->onHead('/hello', function() {});
        $routing->onGet('/foo', function() {});
        $this->assertInstanceOf('net\stubbles\webapp\OptionsRoute',
                                $routing->findRoute()
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
                                $this->routing->findRoute()
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
        $this->assertEquals($route, $this->routing->findRoute());
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
                                          ->findRoute()
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
                                          ->findRoute()
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
                                          ->findRoute()
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
                                          ->findRoute()
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
                                          ->findRoute()
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
                                          ->findRoute()
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
                                          ->findRoute()
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
                                          ->findRoute()
        );
    }

    /**
     * @test
     */
    public function supportsNoMimeTypeByDefault()
    {
        $this->assertEquals(array(),
                            $this->routing->findRoute()
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
                                          ->findRoute()
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
        $this->assertFalse($this->routing->findRoute()
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
                                        ->findRoute()
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
                                        ->findRoute()
                                        ->getSupportedMimeTypes()
                                        ->isContentNegotationDisabled()
        );
    }
}

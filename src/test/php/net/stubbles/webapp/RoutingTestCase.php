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
use net\stubbles\peer\http\AcceptHeader;
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
    public function returnsNullOnRouteSelectionWhenNoRouteAdded()
    {
        $this->assertNull($this->routing->findRoute());
    }

    /**
     * @test
     */
    public function canNotFindAnyRouteForPathWhenNoRouteAdded()
    {
        $this->assertFalse($this->routing->canFindRouteWithAnyMethod());
    }

    /**
     * @test
     */
    public function canNotFindRouteForPathWhenNoRouteAdded()
    {
        $this->assertFalse($this->routing->canFindRoute());
    }

    /**
     * @test
     */
    public function hasNoAllowedMethodsWhenNoRouteAdded()
    {
        $this->assertEquals(array(), $this->routing->getAllowedMethods());
    }

    /**
     * @test
     */
    public function returnsNullOnRouteSelectionWhenNoSuitableRouteAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertNull($this->routing->findRoute());
    }

    /**
     * @test
     */
    public function canFindAnyRouteWhenRouteForOtherMethodAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onPost('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertTrue($this->routing->canFindRouteWithAnyMethod());
    }

    /**
     * @test
     */
    public function canNotFindRouteWhenRouteForOtherMethodAddedOnly()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onPost('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertFalse($this->routing->canFindRoute());
    }

    /**
     * @test
     */
    public function hasListOfAllowedMethodsWhenRouteForOtherMethodAdded()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onPost('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->routing->onPut('/foo', function() {});
        $this->routing->onDelete('/foo', function() {});
        $this->assertEquals(array('HEAD', 'POST'),
                            $this->routing->getAllowedMethods()
        );
    }

    /**
     * @test
     */
    public function listOfAllowedMethodsIncludesHeadWhenGetIsAvailable()
    {
        $this->routing->onGet('/hello', function() {});
        $this->routing->onPut('/hello', function() {});
        $this->assertEquals(array('GET', 'PUT', 'HEAD'),
                            $this->routing->getAllowedMethods()
        );
    }

    /**
     * @test
     */
    public function listOfAllowedMethodsDoesNotIncludeHeadTwiceWhenHeadExplicitlySet()
    {
        $this->routing->onHead('/hello', function() {});
        $this->routing->onGet('/hello', function() {});
        $this->routing->onPut('/hello', function() {});
        $this->assertEquals(array('HEAD', 'GET', 'PUT'),
                            $this->routing->getAllowedMethods()
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
        return new ProcessableRoute($route,
                                    UriRequest::fromString('http://example.net/hello', 'GET'),
                                    $preInterceptors,
                                    $postInterceptors,
                                    $this->getMockBuilder('net\stubbles\ioc\Injector')
                                         ->disableOriginalConstructor()
                                         ->getMock()
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
                            $this->routing->getSupportedMimeTypes()
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
                                          ->getSupportedMimeTypes()
        );
    }

    /**
     * @test
     */
    public function returnsHtmlMimeTypeFromRouteWhenAcceptHeaderIsEmptyAndNoMimeTypeConfigured()
    {

        $this->routing->onGet('/hello', function() {});
        $this->assertEquals('text/html',
                            $this->routing->negotiateMimeType(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeWhenAcceptHeaderIsEmpty()
    {

        $this->assertEquals('application/json',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->negotiateMimeType(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsFirstMimeTypeFromRouteWhenAcceptHeaderIsEmpty()
    {

        $this->routing->onGet('/hello', function() {})
                      ->supportsMimeType('application/xml');
        $this->assertEquals('application/xml',
                            $this->routing->supportsMimeType('application/json')
                                          ->negotiateMimeType(new AcceptHeader())
        );
    }

    /**
     * @test
     */
    public function returnsHtmlMimeTypeWithGreatesPriorityAccordingToAcceptHeader()
    {
        $this->routing->onGet('/hello', function() {});
        $this->assertEquals('text/html',
                            $this->routing->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'))
        );
    }

    /**
     * @test
     */
    public function returnsMimeTypeWithGreatesPriorityAccordingToAcceptHeader()
    {
        $this->routing->onGet('/hello', function() {});
        $this->assertEquals('text/html',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->supportsMimeType('text/html')
                                          ->supportsMimeType('text/plain')
                                          ->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'))
        );
    }

    /**
     * @test
     */
    public function returnsBestMatchMimeTypeAccordingToAcceptHeader()
    {
        $this->routing->onGet('/hello', function() {});
        $this->assertEquals('application/json',
                            $this->routing->supportsMimeType('application/json')
                                          ->supportsMimeType('application/xml')
                                          ->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7, application/json;q=0.4, */*;q=0.5'))
        );
    }

    /**
     * @test
     */
    public function returnsNoMimeTypeWhenNoMatchWithAcceptHeaderFound()
    {
        $this->assertNull($this->routing->supportsMimeType('application/json')
                                        ->supportsMimeType('application/xml')
                                        ->negotiateMimeType(AcceptHeader::parse('text/*;q=0.3, text/html;q=0.7'))
        );
    }
}
?>
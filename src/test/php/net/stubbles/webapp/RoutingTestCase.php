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
     * mocked interceptor handler
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInterceptorHandler;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockInterceptorHandler = $this->getMockBuilder('net\stubbles\webapp\interceptor\InterceptorHandler')
                                             ->disableOriginalConstructor()
                                             ->getMock();
        $this->routing                = new Routing(UriRequest::fromString('http://example.net/hello', 'GET'),
                                                    $this->mockInterceptorHandler
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
     * @test
     */
    public function returnsRouteWhichFitsMethodAndPath()
    {
        $route = $this->routing->onGet('/hello', function() {});
        $this->routing->onGet('/foo', function() {});
        $this->assertSame($route, $this->routing->findRoute());
    }

    /**
     * @test
     */
    public function hasNoGlobalPreInterceptorsByDefault()
    {
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPreInterceptors')
                                     ->with($this->equalTo(array()),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->applyPreInterceptors($mockRequest, $mockResponse));
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
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPreInterceptors')
                                     ->with($this->equalTo(array()),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->preInterceptOnHead($preInterceptor)
                                        ->preInterceptOnPost($preInterceptor)
                                        ->preInterceptOnPut($preInterceptor)
                                        ->preInterceptOnDelete($preInterceptor)
                                        ->applyPreInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenNoRouteSelected()
    {
        $preInterceptor = function() {};
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPreInterceptors')
                                     ->with($this->equalTo(array('array_map', $preInterceptor)),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->preIntercept('array_map')
                                        ->preInterceptOnGet($preInterceptor)
                                        ->applyPreInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPreInterceptorsEvenWhenRouteSelected()
    {
        $this->routing->onGet('/hello', function() {});
        $preInterceptor     = function() {};
        $mockPreInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PreInterceptor');
        $mockPreFunction    = 'array_map';
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPreInterceptors')
                                     ->with($this->equalTo(array(get_class($mockPreInterceptor),
                                                                 $preInterceptor,
                                                                 $mockPreInterceptor,
                                                                 $mockPreFunction
                                                           )
                                            ),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->preIntercept(get_class($mockPreInterceptor))
                                        ->preIntercept($preInterceptor)
                                        ->preIntercept($mockPreInterceptor)
                                        ->preIntercept($mockPreFunction)
                                        ->applyPreInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePreInterceptors()
    {
        $this->routing->onGet('/hello', function() {})
                      ->preIntercept('array_map');
        $preInterceptor = function() {};
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPreInterceptors')
                                     ->with($this->equalTo(array($preInterceptor,
                                                                 'array_map'
                                                           )
                                            ),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->preInterceptOnGet($preInterceptor)
                                        ->applyPreInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function hasNoGlobalPostInterceptorsByDefault()
    {
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPostInterceptors')
                                     ->with($this->equalTo(array()),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->applyPostInterceptors($mockRequest, $mockResponse));
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
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPostInterceptors')
                                     ->with($this->equalTo(array()),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->postInterceptOnHead($postInterceptor)
                                        ->postInterceptOnPost($postInterceptor)
                                        ->postInterceptOnPut($postInterceptor)
                                        ->postInterceptOnDelete($postInterceptor)
                                        ->applyPostInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenNoRouteSelected()
    {
        $postInterceptor = function() {};
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPostInterceptors')
                                     ->with($this->equalTo(array('array_map', $postInterceptor)),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->postIntercept('array_map')
                                        ->postInterceptOnGet($postInterceptor)
                                        ->applyPostInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function hasGlobalPostInterceptorsEvenWhenRouteSelected()
    {
        $this->routing->onGet('/hello', function() {});
        $postInterceptor     = function() {};
        $mockPostInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PostInterceptor');
        $mockPostFunction    = 'array_map';
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPostInterceptors')
                                     ->with($this->equalTo(array(get_class($mockPostInterceptor),
                                                                 $postInterceptor,
                                                                 $mockPostInterceptor,
                                                                 $mockPostFunction
                                                           )
                                            ),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->postIntercept(get_class($mockPostInterceptor))
                                        ->postIntercept($postInterceptor)
                                        ->postIntercept($mockPostInterceptor)
                                        ->postIntercept($mockPostFunction)
                                        ->applyPostInterceptors($mockRequest, $mockResponse)
        );
    }

    /**
     * @test
     */
    public function mergesGlobalAndRoutePostInterceptors()
    {
        $this->routing->onGet('/hello', function() {})
                      ->postIntercept('array_map');
        $postInterceptor = function() {};
        $mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInterceptorHandler->expects($this->once())
                                     ->method('applyPostInterceptors')
                                     ->with($this->equalTo(array('array_map', $postInterceptor)),
                                            $this->equalTo($mockRequest),
                                            $this->equalTo($mockResponse)
                                       )
                                     ->will($this->returnValue(true));
        $this->assertTrue($this->routing->postInterceptOnGet($postInterceptor)
                                        ->applyPostInterceptors($mockRequest, $mockResponse)
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
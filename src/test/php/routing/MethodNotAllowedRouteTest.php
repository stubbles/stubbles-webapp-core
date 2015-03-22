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
use stubbles\webapp\UriRequest;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Tests for stubbles\webapp\routing\MethodNotAllowedRoute.
 *
 * @since  2.2.0
 * @group  routing
 */
class MethodNotAllowedRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  MethodNotAllowedRoute
     */
    private $methodNotAllowedRoute;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->methodNotAllowedRoute = new MethodNotAllowedRoute(new UriRequest('http://example.com/hello/world', 'GET'),
                                                                 $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                                                                      ->disableOriginalConstructor()
                                                                      ->getMock(),
                                                                 new SupportedMimeTypes([]),
                                                                 ['GET', 'POST', 'HEAD']
                                       );
        $this->mockRequest  = $this->getMock('stubbles\webapp\request\Request');
        $this->mockResponse = $this->getMock('stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        $this->assertFalse($this->methodNotAllowedRoute->requiresHttps());
    }

    /**
     * @test
     */
    public function processTriggers405MethodNotAllowedResponse()
    {
        $this->mockRequest->expects($this->once())
                          ->method('method')
                          ->will($this->returnValue('DELETE'));
        $this->mockResponse->expects($this->once())
                           ->method('methodNotAllowed')
                           ->with($this->equalTo('DELETE'), $this->equalTo(['GET', 'POST', 'HEAD', 'OPTIONS']));
        $this->assertTrue($this->methodNotAllowedRoute->process($this->mockRequest, $this->mockResponse));
    }
}
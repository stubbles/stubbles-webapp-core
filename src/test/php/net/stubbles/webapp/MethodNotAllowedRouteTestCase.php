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
use net\stubbles\webapp\response\SupportedMimeTypes;
/**
 * Tests for net\stubbles\webapp\MethodNotAllowedRoute.
 *
 * @since  2.2.0
 * @group  core
 */
class MethodNotAllowedRouteTestCase extends \PHPUnit_Framework_TestCase
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
        $this->methodNotAllowedRoute = new MethodNotAllowedRoute(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                                                                 $this->getMockBuilder('net\stubbles\webapp\interceptor\Interceptors')
                                                                      ->disableOriginalConstructor()
                                                                      ->getMock(),
                                                                 new SupportedMimeTypes(array()),
                                                                 array('GET', 'POST', 'HEAD')
                                       );
        $this->mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        $this->assertFalse($this->methodNotAllowedRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function doesNotRequireAuth()
    {
        $this->assertFalse($this->methodNotAllowedRoute->requiresAuth());
    }

    /**
     * @test
     */
    public function isAlwaysAuthorized()
    {
        $this->assertTrue($this->methodNotAllowedRoute->isAuthorized($this->getMock('net\stubbles\webapp\AuthHandler')));
    }

    /**
     * @test
     */
    public function doesNotRequireLogin()
    {
        $this->assertFalse($this->methodNotAllowedRoute->requiresLogin($this->getMock('net\stubbles\webapp\AuthHandler')));
    }

    /**
     * @test
     */
    public function processTriggers405MethodNotAllowedResponse()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getMethod')
                          ->will($this->returnValue('DELETE'));
        $this->mockResponse->expects($this->once())
                           ->method('methodNotAllowed')
                           ->with($this->equalTo('DELETE'), $this->equalTo(array('GET', 'POST', 'HEAD', 'OPTIONS')));
        $this->assertTrue($this->methodNotAllowedRoute->process($this->mockRequest, $this->mockResponse));
    }
}
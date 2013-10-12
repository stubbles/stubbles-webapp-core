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
 * Tests for net\stubbles\webapp\OptionsRoute.
 *
 * @since  2.2.0
 * @group  core
 */
class OptionsRouteTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  OptionsRoute
     */
    private $optionsRoute;
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
        $this->optionsRoute = new OptionsRoute(UriRequest::fromString('http://example.com/hello/world', 'GET'),
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
        $this->assertFalse($this->optionsRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function doesNotRequireAnyRole()
    {
        $this->assertFalse($this->optionsRoute->requiresRole());
    }

    /**
     * @test
     */
    public function hasNoRequiredRole()
    {
        $this->assertNull($this->optionsRoute->getRequiredRole());
    }

    /**
     * @test
     */
    public function processAddsAllowHeadersWhenRequestMethodIsOptions()
    {
        $this->mockResponse->expects($this->at(0))
                           ->method('addHeader')
                           ->with($this->equalTo('Allow'), $this->equalTo('GET, POST, HEAD, OPTIONS'))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->at(1))
                           ->method('addHeader')
                           ->with($this->equalTo('Access-Control-Allow-Methods'), $this->equalTo('GET, POST, HEAD, OPTIONS'))
                           ->will($this->returnSelf());
        $this->assertTrue($this->optionsRoute->process($this->mockRequest, $this->mockResponse));
    }
}
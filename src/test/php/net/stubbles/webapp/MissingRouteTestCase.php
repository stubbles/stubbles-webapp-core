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
 * Tests for net\stubbles\webapp\MissingRoute.
 *
 * @since  2.2.0
 * @group  core
 */
class MissingRouteTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  MissingRoute
     */
    private $missingRoute;
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
        $this->missingRoute = new MissingRoute(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                                               $this->getMockBuilder('net\stubbles\webapp\interceptor\Interceptors')
                                                    ->disableOriginalConstructor()
                                                    ->getMock(),
                                               new SupportedMimeTypes(array())
                              );
        $this->mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        $this->assertFalse($this->missingRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function processTriggers404NotFoundResponse()
    {
        $this->mockResponse->expects($this->once())
                           ->method('notFound');
        $this->assertTrue($this->missingRoute->process($this->mockRequest, $this->mockResponse));
    }
}
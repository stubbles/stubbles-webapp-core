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
 * Tests for stubbles\webapp\routing\MissingRoute.
 *
 * @since  2.2.0
 * @group  core
 */
class MissingRouteTest extends \PHPUnit_Framework_TestCase
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
        $this->missingRoute = new MissingRoute(new UriRequest('http://example.com/hello/world', 'GET'),
                                               $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                                                    ->disableOriginalConstructor()
                                                    ->getMock(),
                                               new SupportedMimeTypes([])
                              );
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        $this->assertFalse($this->missingRoute->requiresHttps());
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
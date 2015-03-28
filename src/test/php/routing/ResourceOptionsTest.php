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
/**
 * Tests for stubbles\webapp\routing\ResourceOptions.
 *
 * @since  2.2.0
 * @group  routing
 */
class ResourceOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\ResourceOptions
     */
    private $resourceOptions;
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
        $this->resourceOptions = new ResourceOptions(
                $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new CalledUri('http://example.com/hello/world', 'GET'),
                $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new SupportedMimeTypes([]),
                ['GET', 'POST', 'HEAD']
        );
        $this->mockRequest  = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse = $this->getMock('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        $this->assertFalse($this->resourceOptions->requiresHttps());
    }

    /**
     * @test
     */
    public function addsAllowHeadersWhenRequestMethodIsOptions()
    {
        $this->mockResponse->expects($this->at(0))
                           ->method('addHeader')
                           ->with($this->equalTo('Allow'), $this->equalTo('GET, POST, HEAD, OPTIONS'))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->at(1))
                           ->method('addHeader')
                           ->with(
                                   $this->equalTo('Access-Control-Allow-Methods'),
                                   $this->equalTo('GET, POST, HEAD, OPTIONS')
                            )
                           ->will($this->returnSelf());
        $this->resourceOptions->resolve(
                $this->mockRequest,
                $this->mockResponse
        );
    }
}
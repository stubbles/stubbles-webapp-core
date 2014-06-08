<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Tests for stubbles\webapp\InternalServerErrorRoute.
 *
 * @since  3.0.0
 * @group  core
 */
class InternalServerErrorRouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  InternalServerErrorRoute
     */
    private $internalServerErrorRoute;
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
        $this->internalServerErrorRoute = new InternalServerErrorRoute('error',
                                                                       UriRequest::fromString('http://example.com/hello/world', 'GET'),
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
        $this->assertFalse($this->internalServerErrorRoute->switchToHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals('https://example.com/hello/world',
                            (string) $this->internalServerErrorRoute->getHttpsUri()
        );
    }

    /**
     * @test
     */
    public function returnsGivenListOfSupportedMimeTypes()
    {
        $this->assertEquals(new SupportedMimeTypes([]),
                            $this->internalServerErrorRoute->getSupportedMimeTypes()
        );
    }

    /**
     * @test
     */
    public function applyPreInterceptorsTriggersInternalServerError()
    {
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('error'));
        $this->assertFalse($this->internalServerErrorRoute->applyPreInterceptors($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function processReturnsFalse()
    {
        $this->assertFalse($this->internalServerErrorRoute->process($this->mockRequest, $this->mockResponse));
    }

    /**
     * @test
     */
    public function applyPostInterceptorsReturnsFalse()
    {
        $this->assertFalse($this->internalServerErrorRoute->applyPostInterceptors($this->mockRequest, $this->mockResponse));
    }
}
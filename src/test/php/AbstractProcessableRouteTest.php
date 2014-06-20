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
use stubbles\input\web\WebRequest;
use stubbles\webapp\auth\AuthHandler;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Helper class for the test.
 */
class TestAbstractProcessableRoute extends AbstractProcessableRoute
{
    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps() {}

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresAuth() {}

    /**
     * checks whether this is an authorized request to this route
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function isAuthorized(AuthHandler $authHandler) {}

    /**
     * checks whether route required login
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function requiresLogin(AuthHandler $authHandler) {}

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response) {}
}
/**
 * Tests for stubbles\webapp\AbstractProcessableRoute.
 *
 * @since  2.0.0
 * @group  core
 */
class AbstractProcessableRouteTest extends \PHPUnit_Framework_TestCase
{
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
     * mocked injector instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInterceptors;
    /**
     * mocked list of supported mime types
     *
     * @type  SupportedMimeTypes
     */
    private $supportedMimeTypes;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('stubbles\webapp\response\Response');
        $this->mockInterceptors = $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->supportedMimeTypes = new SupportedMimeTypes([]);
    }

    /**
     * creates instance to test
     *
     * @param   array     $preInterceptors
     * @param   array     $postInterceptors
     * @return  ProcessableRoute
     */
    private function createRoute()
    {
        return new TestAbstractProcessableRoute(new UriRequest('http://example.com/hello/world', 'GET'),
                                                $this->mockInterceptors,
                                                $this->supportedMimeTypes

        );
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals('https://example.com/hello/world',
                            (string) $this->createRoute()->httpsUri()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsGivenListOfSupportedMimeTypes()
    {
        $this->assertSame($this->supportedMimeTypes,
                          $this->createRoute()->supportedMimeTypes()
        );
    }

    /**
     * @test
     */
    public function delegatesPreInterceptingToInterceptors()
    {
        $this->mockInterceptors->expects($this->once())
                           ->method('preProcess')
                           ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                           ->will($this->returnValue(true));
        $this->assertTrue($this->createRoute()
                               ->applyPreInterceptors($this->mockRequest,
                                                      $this->mockResponse
                                 )
        );
    }

    /**
     * @test
     */
    public function delegatesPostInterceptingToInterceptors()
    {
        $this->mockInterceptors->expects($this->once())
                           ->method('postProcess')
                           ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                           ->will($this->returnValue(true));
        $this->assertTrue($this->createRoute()
                                ->applyPostInterceptors($this->mockRequest,
                                                        $this->mockResponse
                                  )
        );
    }
}

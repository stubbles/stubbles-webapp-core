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
use net\stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\SupportedMimeTypes;
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
    public function switchToHttps() {}

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresRole() {}

    /**
     * checks whether this is an authorized request to this route
     *
     * @return  bool
     */
    public function getRequiredRole() {}

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
 * Tests for net\stubbles\webapp\AbstractProcessableRoute.
 *
 * @since  2.0.0
 * @group  core
 */
class AbstractProcessableRouteTestCase extends \PHPUnit_Framework_TestCase
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
    private $mockInjector;
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
        $this->mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInjector = $this->getMockBuilder('net\stubbles\ioc\Injector')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->supportedMimeTypes = new SupportedMimeTypes(array());
    }

    /**
     * creates instance to test
     *
     * @param   array     $preInterceptors
     * @param   array     $postInterceptors
     * @return  ProcessableRoute
     */
    private function createRoute(array $preInterceptors = array(), array $postInterceptors = array())
    {
        return new TestAbstractProcessableRoute(UriRequest::fromString('http://example.com/hello/world', 'GET'),
                                                $preInterceptors,
                                                $postInterceptors,
                                                $this->mockInjector,
                                                $this->supportedMimeTypes

        );
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals('https://example.com/hello/world',
                            (string) $this->createRoute()->getHttpsUri()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsGivenListOfSupportedMimeTypes()
    {
        $this->assertSame($this->supportedMimeTypes,
                          $this->createRoute()->getSupportedMimeTypes()
        );
    }

    /**
     * a callback
     *
     * @param  WebRequest  $request
     * @param  Response    $response
     */
    public function callableMethod(WebRequest $request, Response $response)
    {
        $response->addHeader('X-Binford', '6100 (More power!)');
    }

    /**
     * @test
     */
    public function doesNotCallOtherPreInterceptorsIfOneCancelsRequest()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PreInterceptor')));
        $this->assertFalse($this->createRoute(array('some\PreInterceptor',
                                                    'other\PreInterceptor'
                                              )
                                  )
                                ->applyPreInterceptors($this->mockRequest,
                                                       $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenRequestNotCancelledByAnyPreInterceptor()
    {
        $mockPreInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PreInterceptor');
        $mockPreInterceptor->expects($this->exactly(2))
                           ->method('preProcess')
                           ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse));
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($mockPreInterceptor));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('addHeader');
        $this->assertTrue($this->createRoute(array('some\PreInterceptor',
                                                   $mockPreInterceptor,
                                                   function(WebRequest $request, Response $response)
                                                   {
                                                       $response->setStatusCode(418);
                                                   },
                                                   array($this, 'callableMethod')
                                             )
                                  )
                                ->applyPreInterceptors($this->mockRequest,
                                                       $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function doesNotCallOtherPostInterceptorsIfOneCancelsRequest()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PostInterceptor')));
        $this->assertFalse($this->createRoute(array(),
                                              array('some\PostInterceptor',
                                                    'other\PostInterceptor'
                                              )
                                  )
                                ->applyPostInterceptors($this->mockRequest,
                                                        $this->mockResponse
                                  )
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenRequestNotCancelledByAnyPostInterceptor()
    {
        $mockPostInterceptor = $this->getMock('net\stubbles\webapp\interceptor\PostInterceptor');
        $mockPostInterceptor->expects($this->exactly(2))
                            ->method('postProcess')
                            ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse));
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($mockPostInterceptor));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->once())
                           ->method('addHeader');
        $this->assertTrue($this->createRoute(array(),
                                             array('some\PostInterceptor',
                                                   $mockPostInterceptor,
                                                   function(WebRequest $request, Response $response)
                                                   {
                                                       $response->setStatusCode(418);
                                                   },
                                                   array($this, 'callableMethod')
                                             )
                                  )
                                ->applyPostInterceptors($this->mockRequest,
                                                        $this->mockResponse
                                  )
        );
    }
}

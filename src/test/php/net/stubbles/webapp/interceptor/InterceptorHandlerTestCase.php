<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\interceptor;
use net\stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
/**
 * Tests for net\stubbles\webapp\WebApp.
 *
 * @since  2.0.0
 * @group  interceptor
 */
class InterceptorHandlerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  InterceptorHandler
     */
    private $interceptorHandler;
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
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest        = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse       = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInjector       = $this->getMockBuilder('net\stubbles\ioc\Injector')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $this->interceptorHandler = new InterceptorHandler($this->mockInjector);
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
                          ->will($this->onConsecutiveCalls(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PreInterceptor')));
        $this->assertFalse($this->interceptorHandler->applyPreInterceptors(array('some\PreInterceptor',
                                                                                 'other\PreInterceptor'
                                                                           ),
                                                                           $this->mockRequest,
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
        $this->assertTrue($this->interceptorHandler->applyPreInterceptors(array('some\PreInterceptor',
                                                                                $mockPreInterceptor,
                                                                                function(WebRequest $request, Response $response)
                                                                                {
                                                                                    $response->setStatusCode(418);
                                                                                },
                                                                                array($this, 'callableMethod')
                                                                          ),
                                                                          $this->mockRequest,
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
                          ->will($this->onConsecutiveCalls(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('some\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\interceptor\PostInterceptor')));
        $this->assertFalse($this->interceptorHandler->applyPostInterceptors(array('some\PostInterceptor',
                                                                                  'other\PostInterceptor'
                                                                            ),
                                                                            $this->mockRequest,
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
        $this->assertTrue($this->interceptorHandler->applyPostInterceptors(array('some\PostInterceptor',
                                                                                 $mockPostInterceptor,
                                                                                 function(WebRequest $request, Response $response)
                                                                                 {
                                                                                     $response->setStatusCode(418);
                                                                                 },
                                                                                 array($this, 'callableMethod')
                                                                           ),
                                                                           $this->mockRequest,
                                                                           $this->mockResponse
                          )
        );
    }
}
?>
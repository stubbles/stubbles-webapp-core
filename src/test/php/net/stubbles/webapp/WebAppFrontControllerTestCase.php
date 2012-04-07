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
use net\stubbles\peer\http\HttpUri;
use net\stubbles\webapp\processor\ProcessorException;
/**
 * Tests for net\stubbles\webapp\WebAppFrontController.
 *
 * @since  1.7.0
 * @group  webapp
 */
class WebAppFrontControllerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  WebAppFrontController
     */
    private $webAppFrontController;
    /**
     * mocked contains request data
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response container
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
     * mocked uri configuration
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockUriConfig;
    /**
     * the mocked processor
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockProcessor;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest           = $this->getMock('net\\stubbles\\input\\web\\WebRequest');
        $this->mockResponse          = $this->getMock('net\\stubbles\\webapp\\response\\Response');
        $this->mockInjector          = $this->getMockBuilder('net\\stubbles\\ioc\\Injector')
                                            ->disableOriginalConstructor()
                                            ->getMock();
        $this->mockUriConfig         = $this->getMockBuilder('net\\stubbles\\webapp\\UriConfiguration')
                                            ->disableOriginalConstructor()
                                            ->getMock();
        $this->webAppFrontController = new WebAppFrontController($this->mockRequest,
                                                                 $this->mockResponse,
                                                                 $this->mockInjector,
                                                                 $this->mockUriConfig
                                       );
        $this->mockProcessor         = $this->getMock('net\\stubbles\\webapp\\processor\\Processor');
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue($this->webAppFrontController->getClass()
                                                      ->getConstructor()
                                                      ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function processWithAlreadyCancelledRequestNeverCallsInterceptorsOrProcessor()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->never())
                          ->method('getUri');
        $this->mockUriConfig->expects($this->never())
                            ->method('getPreInterceptors');
        $this->mockUriConfig->expects($this->never())
                            ->method('getProcessorName');
        $this->mockUriConfig->expects($this->never())
                            ->method('getPostInterceptors');
        $this->mockInjector->expects($this->never())
                           ->method('getInstance');
        $this->webAppFrontController->process();
    }

    /**
     * prepares pre interceptors
     */
    private function preparePreInterceptors()
    {
        $this->mockUriConfig->expects($this->once())
                            ->method('getPreInterceptors')
                            ->will($this->returnValue(array('my\\PreInterceptor',
                                                            'other\\PreInterceptor'
                                                      )
                                   )
                              );
        $this->mockInjector->expects($this->at(0))
                           ->method('getInstance')
                           ->with($this->equalTo('my\\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\\stubbles\\webapp\\interceptor\\PreInterceptor')));
        $this->mockInjector->expects($this->at(1))
                           ->method('getInstance')
                           ->with($this->equalTo('other\\PreInterceptor'))
                           ->will($this->returnValue($this->getMock('net\\stubbles\\webapp\\interceptor\\PreInterceptor')));
    }

    /**
     * @test
     */
    public function processStopsIfPreInterceptorCancelsRequest()
    {
        $this->mockRequest->expects($this->exactly(3))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, true));
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.net/xml/Home')));
        $this->preparePreInterceptors();
        $this->mockUriConfig->expects($this->never())
                            ->method('getProcessorName');
        $this->mockUriConfig->expects($this->never())
                            ->method('getPostInterceptors');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webAppFrontController->process();
    }

    /**
     * prepares processor
     */
    private function prepareProcessor()
    {
        $this->mockUriConfig->expects($this->once())
                            ->method('getProcessorName')
                            ->will($this->returnValue('example'));
        $this->mockInjector->expects($this->at(2))
                           ->method('getInstance')
                           ->with($this->equalTo('net\\stubbles\\webapp\\processor\\Processor'),
                                  $this->equalTo('example')
                             )
                           ->will($this->returnValue($this->mockProcessor));
    }

    /**
     * @test
     */
    public function processStopsIfProcessorCancelsRequest()
    {
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false, true));
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.net/xml/Home')));
        $this->preparePreInterceptors();
        $this->mockUriConfig->expects($this->never())
                            ->method('getPostInterceptors');
        $this->prepareProcessor();
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webAppFrontController->process();
    }

    /**
     * @test
     */
    public function processStopsIfProcessorThrowsException()
    {
        $this->mockRequest->expects($this->exactly(4))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false, true));
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.net/xml/Home')));
        $this->preparePreInterceptors();
        $this->mockUriConfig->expects($this->never())
                            ->method('getPostInterceptors');
        $this->prepareProcessor();
        $this->mockProcessor->expects($this->once())
                            ->method('startup')
                            ->will($this->throwException(new ProcessorException(500, 'Error during processing.')));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(500));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webAppFrontController->process();
    }

    /**
     * @test
     */
    public function processStopsIfProcessorRequiresSslButIsNotSslAndRedirectsToSslVersion()
    {
        $this->mockRequest->expects($this->exactly(3))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false));
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.net/xml/Home')));
        $this->preparePreInterceptors();
        $this->mockUriConfig->expects($this->never())
                            ->method('getPostInterceptors');
        $this->prepareProcessor();
        $this->mockProcessor->expects($this->once())
                            ->method('startup');
        $this->mockProcessor->expects($this->once())
                            ->method('forceSsl')
                            ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('redirect')
                           ->with($this->equalTo(HttpUri::fromString('https://example.net/xml/Home')));
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webAppFrontController->process();
    }

    /**
     * @test
     */
    public function processStopsIfPostInterceptorCancelsRequest()
    {
        $this->mockRequest->expects($this->exactly(5))
                          ->method('isCancelled')
                          ->will($this->onConsecutiveCalls(false, false, false, false, true));
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.net/xml/Home')));
        $this->preparePreInterceptors();
        $this->prepareProcessor();
        $this->mockUriConfig->expects($this->once())
                            ->method('getPostInterceptors')
                            ->will($this->returnValue(array('my\\PostInterceptor',
                                                            'other\\PostInterceptor'
                                                      )
                                   )
                              );
        $this->mockInjector->expects($this->at(3))
                           ->method('getInstance')
                           ->with($this->equalTo('my\\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\\stubbles\\webapp\\interceptor\\PostInterceptor')));
        $this->mockProcessor->expects($this->once())
                            ->method('startup');
        $this->mockProcessor->expects($this->once())
                            ->method('process');
        $this->mockProcessor->expects($this->once())
                            ->method('cleanup');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webAppFrontController->process();
    }

    /**
     * @test
     */
    public function processNeversStopsIfRequestNotCancelled()
    {
        $this->mockRequest->expects($this->any())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockRequest->expects($this->once())
                          ->method('getUri')
                          ->will($this->returnValue(HttpUri::fromString('http://example.net/xml/Home')));
        $this->preparePreInterceptors();
        $this->prepareProcessor();
        $this->mockUriConfig->expects($this->once())
                            ->method('getPostInterceptors')
                            ->will($this->returnValue(array('my\\PostInterceptor',
                                                            'other\\PostInterceptor'
                                                      )
                                   )
                              );
        $this->mockInjector->expects($this->at(3))
                           ->method('getInstance')
                           ->with($this->equalTo('my\\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\\stubbles\\webapp\\interceptor\\PostInterceptor')));
        $this->mockInjector->expects($this->at(4))
                           ->method('getInstance')
                           ->with($this->equalTo('other\\PostInterceptor'))
                           ->will($this->returnValue($this->getMock('net\\stubbles\\webapp\\interceptor\\PostInterceptor')));
        $this->mockProcessor->expects($this->once())
                            ->method('startup');
        $this->mockProcessor->expects($this->once())
                            ->method('process');
        $this->mockProcessor->expects($this->once())
                            ->method('cleanup');
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->webAppFrontController->process();
    }
}
?>
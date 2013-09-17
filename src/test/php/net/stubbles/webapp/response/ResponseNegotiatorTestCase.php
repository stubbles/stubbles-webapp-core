<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
use net\stubbles\lang\reflect\ReflectionObject;
/**
 * Tests for net\stubbles\webapp\response\ResponseNegotiator.
 *
 * @since  2.0.0
 * @group  response
 */
class ResponseNegotiatorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ResponseNegotiator
     */
    private $responseNegotiator;
    /**
     * mocked base response
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;
    /**
     * mocked injector
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked routing configuration
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRouting;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockResponse       = $this->getMock('net\stubbles\webapp\response\Response');
        $this->mockInjector       = $this->getMockBuilder('net\stubbles\ioc\Injector')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $this->responseNegotiator = new ResponseNegotiator($this->mockResponse, $this->mockInjector);
        $this->mockRequest        = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockRouting        = $this->getMockBuilder('net\stubbles\webapp\Routing')
                                         ->disableOriginalConstructor()
                                         ->getMock();
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue(ReflectionObject::fromInstance($this->responseNegotiator)
                                          ->getConstructor()
                                          ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function createWithUnsupportedProtocolRespondsWithHttpVersionNotSupported()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue(null));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $mockResponseClass = get_class($this->getMockBuilder('net\stubbles\webapp\response\WebResponse')
                                            ->setMethods(array('header', 'sendBody'))
                                            ->getMock()
                             );
        $response = ResponseNegotiator::negotiateHttpVersion($this->mockRequest, $mockResponseClass);
        $this->assertInstanceOf($mockResponseClass, $response);
        $response->expects($this->once())
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.1 505 HTTP Version Not Supported'));
        $response->expects($this->once())
                 ->method('sendBody')
                 ->with($this->equalTo('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1'));
    }

    /**
     * @test
     */
    public function createWithSupportedProtocolVersion1_0()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue('1.0'));
        $this->mockRequest->expects($this->never())
                          ->method('cancel');
        $mockResponseClass = get_class($this->getMockBuilder('net\stubbles\webapp\response\WebResponse')
                                            ->setMethods(array('header', 'sendBody'))
                                            ->getMock()
                             );
        $response = ResponseNegotiator::negotiateHttpVersion($this->mockRequest, $mockResponseClass);
        $this->assertInstanceOf($mockResponseClass, $response);
        $response->expects($this->once())
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.0 200 OK'));
    }

    /**
     * @test
     */
    public function createWithSupportedProtocolVersionAndDefaultClass()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue('1.1'));
        $this->mockRequest->expects($this->never())
                          ->method('cancel');
        $response = ResponseNegotiator::negotiateHttpVersion($this->mockRequest);
        $this->assertInstanceOf('net\stubbles\webapp\response\WebResponse',
                                $response
        );
    }

    /**
     * mocks acept header to respond with given value
     *
     * @param  string  $value
     */
    private function mockAcceptHeader($value = null)
    {
        $this->mockRequest->expects($this->once())
                          ->method('readHeader')
                          ->with($this->equalTo('HTTP_ACCEPT'))
                          ->will($this->returnValue(\net\stubbles\input\ValueReader::forValue($value)));
    }

    /**
     * @test
     */
    public function doesNotNegotiateMimeTypeWithAlreadyCancelledRequest()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->never())
                          ->method('readHeader');
        $this->mockRouting->expects($this->never())
                          ->method('negotiateMimeType');
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest, $this->mockRouting)
        );
    }

    /**
     * @test
     * @since  2.1.1
     */
    public function doesNotNegotiateMimeTypeWhenContentNegotiationDisabled()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockRequest->expects($this->never())
                          ->method('readHeader');
        $this->mockRouting->expects($this->once())
                          ->method('isContentNegotationDisabled')
                          ->will($this->returnValue(true));
        $this->mockRouting->expects($this->never())
                          ->method('negotiateMimeType');
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest, $this->mockRouting)
        );
    }

    /**
     * @test
     */
    public function failedMimeTypeNegotiationForExistingRouteRespondsWithNotAcceptable()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockAcceptHeader();
        $this->mockRouting->expects($this->once())
                          ->method('isContentNegotationDisabled')
                          ->will($this->returnValue(false));
        $this->mockRouting->expects($this->once())
                          ->method('negotiateMimeType')
                          ->will($this->returnValue(null));
        $this->mockRouting->expects($this->once())
                          ->method('canFindRouteWithAnyMethod')
                          ->will($this->returnValue(true));
        $this->mockRouting->expects($this->once())
                          ->method('getSupportedMimeTypes')
                          ->will($this->returnValue(array('application/json', 'application/xml')));
        $this->mockResponse->expects($this->once())
                           ->method('notAcceptable')
                           ->with($this->equalTo(array('application/json', 'application/xml')));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest, $this->mockRouting)
        );
    }

    /**
     * @test
     */
    public function missingFormatterForNegotiatedMimeTypeRespondsWithInternalServerError()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockAcceptHeader();
        $this->mockRouting->expects($this->once())
                          ->method('isContentNegotationDisabled')
                          ->will($this->returnValue(false));
        $this->mockRouting->expects($this->once())
                          ->method('negotiateMimeType')
                          ->will($this->returnValue('application/json'));
        $this->mockInjector->expects($this->once())
                           ->method('hasBinding')
                           ->with($this->equalTo('net\stubbles\webapp\response\format\Formatter'),
                                  $this->equalTo('application/json')
                             )
                           ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('No formatter defined for negotiated content type application/json'));
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest, $this->mockRouting)
        );
    }

    /**
     * @test
     */
    public function createsFormatterForNegotiatedMimeTypeAndReturnsFormattingResponse()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockAcceptHeader();
        $this->mockRouting->expects($this->once())
                          ->method('isContentNegotationDisabled')
                          ->will($this->returnValue(false));
        $this->mockRouting->expects($this->once())
                          ->method('negotiateMimeType')
                          ->will($this->returnValue('application/json'));
        $this->mockInjector->expects($this->once())
                           ->method('hasBinding')
                           ->with($this->equalTo('net\stubbles\webapp\response\format\Formatter'),
                                  $this->equalTo('application/json')
                             )
                           ->will($this->returnValue(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('net\stubbles\webapp\response\format\Formatter'),
                                  $this->equalTo('application/json')
                             )
                           ->will($this->returnValue($this->getMock('net\stubbles\webapp\response\format\Formatter')));
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with($this->equalTo('Content-type'), $this->equalTo('application/json'));
        $this->mockRequest->expects($this->never())
                          ->method('cancel');
        $this->assertInstanceOf('net\stubbles\webapp\response\FormattingResponse',
                                $this->responseNegotiator->negotiateMimeType($this->mockRequest, $this->mockRouting));
    }

    /**
     * @test
     */
    public function failedMimeTypeNegotiationForNonExistingRouteReturnsFormattingResponse()
    {
        $this->mockRequest->expects($this->once())
                          ->method('isCancelled')
                          ->will($this->returnValue(false));
        $this->mockAcceptHeader();
        $this->mockRouting->expects($this->once())
                          ->method('isContentNegotationDisabled')
                          ->will($this->returnValue(false));
        $this->mockRouting->expects($this->once())
                          ->method('negotiateMimeType')
                          ->will($this->returnValue(null));
        $this->mockRouting->expects($this->once())
                          ->method('canFindRouteWithAnyMethod')
                          ->will($this->returnValue(false));
        $this->mockInjector->expects($this->never())
                           ->method('hasBinding');
        $this->mockInjector->expects($this->never())
                           ->method('getInstance');
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $this->mockRequest->expects($this->never())
                          ->method('cancel');
        $this->assertInstanceOf('net\stubbles\webapp\response\FormattingResponse',
                                $this->responseNegotiator->negotiateMimeType($this->mockRequest, $this->mockRouting));
    }
}
?>
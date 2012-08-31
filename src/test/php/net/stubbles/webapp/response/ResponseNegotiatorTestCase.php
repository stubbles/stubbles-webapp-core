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
        $this->assertTrue($this->responseNegotiator->getClass()
                                                   ->getConstructor()
                                                   ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function createWithUnsupportedProtocolSendsResponseWithStatusCode505()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue(null));
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(505))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1'))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertNull($this->responseNegotiator->negotiate($this->mockRequest, $this->mockRouting));
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
    public function failedMimeTypeNegotiationForExistingRouteSendsResponseWithStatusCode406()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue('1.1'));
        $this->mockAcceptHeader();
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
                           ->method('setStatusCode')
                           ->with($this->equalTo(406))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with($this->equalTo('X-Acceptable'), $this->equalTo('application/json, application/xml'))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertNull($this->responseNegotiator->negotiate($this->mockRequest, $this->mockRouting));
    }

    /**
     * @test
     */
    public function missingFormatterForNegotiatedMimeTypeSendsResponseWithStatusCode506()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue('1.1'));
        $this->mockAcceptHeader();
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
                           ->method('setStatusCode')
                           ->with($this->equalTo(506))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('No formatter defined for negotiated content type application/json'))
                           ->will($this->returnSelf());
        $this->mockResponse->expects($this->once())
                           ->method('send');
        $this->assertNull($this->responseNegotiator->negotiate($this->mockRequest, $this->mockRouting));
    }

    /**
     * @test
     */
    public function createsFormatterForNegotiatedMimeTypeAndReturnsFormattingResponse()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue('1.1'));
        $this->mockAcceptHeader();
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
        $this->mockResponse->expects($this->never())
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->never())
                           ->method('write');
        $this->mockResponse->expects($this->never())
                           ->method('send');
        $this->assertInstanceOf('net\stubbles\webapp\response\FormattingResponse',
                                $this->responseNegotiator->negotiate($this->mockRequest, $this->mockRouting));
    }

    /**
     * @test
     */
    public function failedMimeTypeNegotiationForNonExistingRouteReturnsFormattingResponse()
    {
        $this->mockRequest->expects($this->once())
                          ->method('getProtocolVersion')
                          ->will($this->returnValue('1.1'));
        $this->mockAcceptHeader();
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
                           ->method('setStatusCode');
        $this->mockResponse->expects($this->never())
                           ->method('write');
        $this->mockResponse->expects($this->never())
                           ->method('send');
        $this->assertInstanceOf('net\stubbles\webapp\response\FormattingResponse',
                                $this->responseNegotiator->negotiate($this->mockRequest, $this->mockRouting));
    }
}
?>
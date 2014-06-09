<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\input\ValueReader;
use stubbles\lang;
use stubbles\peer\http\HttpVersion;
/**
 * Tests for stubbles\webapp\response\ResponseNegotiator.
 *
 * @since  2.0.0
 * @group  response
 */
class ResponseNegotiatorTest extends \PHPUnit_Framework_TestCase
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
     * set up test environment
     */
    public function setUp()
    {
        $this->mockResponse       = $this->getMock('stubbles\webapp\response\Response');
        $this->mockInjector       = $this->getMockBuilder('stubbles\ioc\Injector')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $this->responseNegotiator = new ResponseNegotiator($this->mockResponse, $this->mockInjector);
        $this->mockRequest        = $this->getMock('stubbles\input\web\WebRequest');
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->responseNegotiator)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function createWithUnsupportedProtocolRespondsWithHttpVersionNotSupported()
    {
        $this->mockRequest->expects($this->once())
                          ->method('protocolVersion')
                          ->will($this->returnValue(null));
        $mockResponseClass = get_class($this->getMockBuilder('stubbles\webapp\response\WebResponse')
                                            ->setMethods(['header', 'sendBody'])
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
                          ->method('protocolVersion')
                          ->will($this->returnValue(HttpVersion::fromString(HttpVersion::HTTP_1_0)));
        $mockResponseClass = get_class($this->getMockBuilder('stubbles\webapp\response\WebResponse')
                                            ->setMethods(['header', 'sendBody'])
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
                          ->method('protocolVersion')
                          ->will($this->returnValue(HttpVersion::fromString(HttpVersion::HTTP_1_1)));
        $response = ResponseNegotiator::negotiateHttpVersion($this->mockRequest);
        $this->assertInstanceOf('stubbles\webapp\response\WebResponse',
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
                          ->will($this->returnValue(ValueReader::forValue($value)));
    }

    /**
     * @test
     */
    public function doesNotNegotatiateMimeTypeWhenDisabled()
    {
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest,
                                                                       SupportedMimeTypes::createWithDisabledContentNegotation()
                          )
        );
    }

    /**
     * @test
     */
    public function failedMimeTypeNegotiationForExistingRouteRespondsWithNotAcceptable()
    {
        $this->mockAcceptHeader('text/html');
        $this->mockResponse->expects($this->once())
                           ->method('notAcceptable')
                           ->with($this->equalTo(['application/json', 'application/xml']))
                           ->will($this->returnSelf());
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest,
                                                                       new SupportedMimeTypes(['application/json', 'application/xml'])
                          )
        );
    }

    /**
     * @test
     */
    public function missingFormatterForNegotiatedMimeTypeRespondsWithInternalServerError()
    {
        $this->mockAcceptHeader('application/json');
        $this->mockInjector->expects($this->once())
                           ->method('hasBinding')
                           ->with($this->equalTo('stubbles\webapp\response\format\Formatter'),
                                  $this->equalTo('application/json')
                             )
                           ->will($this->returnValue(false));
        $this->mockResponse->expects($this->once())
                           ->method('internalServerError')
                           ->with($this->equalTo('No formatter defined for negotiated content type application/json'))
                           ->will($this->returnSelf());
        $this->assertSame($this->mockResponse,
                          $this->responseNegotiator->negotiateMimeType($this->mockRequest,
                                                                       new SupportedMimeTypes(['application/json', 'application/xml'])
                          )
        );
    }

    /**
     * @test
     */
    public function createsFormatterForNegotiatedMimeTypeAndReturnsFormattingResponse()
    {
        $this->mockAcceptHeader('application/json');
        $this->mockInjector->expects($this->once())
                           ->method('hasBinding')
                           ->with($this->equalTo('stubbles\webapp\response\format\Formatter'),
                                  $this->equalTo('application/json')
                             )
                           ->will($this->returnValue(true));
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('stubbles\webapp\response\format\Formatter'),
                                  $this->equalTo('application/json')
                             )
                           ->will($this->returnValue($this->getMock('stubbles\webapp\response\format\Formatter')));
        $this->assertInstanceOf('stubbles\webapp\response\FormattingResponse',
                                $this->responseNegotiator->negotiateMimeType($this->mockRequest,
                                                                             new SupportedMimeTypes(['application/json', 'application/xml'])
                                )
        );
    }

    /**
     * @test
     * @since  3.2.0
     */
    public function createsSpecializedFormatterForNegotiatedMimeTypeAndReturnsFormattingResponse()
    {
        $this->mockAcceptHeader('application/xml');
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('example\SpecialFormatter'))
                           ->will($this->returnValue($this->getMock('stubbles\webapp\response\format\Formatter')));
        $this->assertInstanceOf('stubbles\webapp\response\FormattingResponse',
                                $this->responseNegotiator->negotiateMimeType($this->mockRequest,
                                                                             new SupportedMimeTypes(['application/json', 'application/xml'],
                                                                                                    ['application/xml' => 'example\SpecialFormatter']
                                                                             )
                                )
        );
    }
}

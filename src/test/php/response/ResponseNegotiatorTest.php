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
use stubbles\lang\reflect;
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
        $this->mockInjector       = $this->getMockBuilder('stubbles\ioc\Injector')
                                         ->disableOriginalConstructor()
                                         ->getMock();
        $this->responseNegotiator = new ResponseNegotiator($this->mockInjector);
        $this->mockRequest        = $this->getMock('stubbles\webapp\request\Request');
    }

    /**
     * @test
     */
    public function annotationPresentOnConstructor()
    {
        $this->assertTrue(
                reflect\annotationsOfConstructor($this->responseNegotiator)
                        ->contain('Inject')
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
        $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $this->responseNegotiator->negotiateMimeType(
                        $this->mockRequest,
                        SupportedMimeTypes::createWithDisabledContentNegotation()
                )->mimeType()
        );
    }

    /**
     * @test
     */
    public function failedMimeTypeNegotiationForExistingRouteRespondsWithNotAcceptable()
    {
        $this->mockAcceptHeader('text/html');
        $response = $this->responseNegotiator->negotiateMimeType(
                $this->mockRequest,
                new SupportedMimeTypes(['application/json', 'application/xml'])
        );

        $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $response->mimeType()
        );
        $this->assertTrue($response->headers()->contain('X-Acceptable'));
        $this->assertEquals(406, $response->statusCode());
    }

    /**
     * @test
     */
    public function missingFormatterForNegotiatedMimeTypeRespondsWithInternalServerError()
    {
        $this->mockAcceptHeader('application/foo');
        $response = $this->responseNegotiator->negotiateMimeType(
                $this->mockRequest,
                new SupportedMimeTypes(['application/foo', 'application/xml'])
        );

        $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $response->mimeType()
        );
        $this->assertEquals(500, $response->statusCode());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function createsFormatterForFirstMimeTypeWhenAcceptHeaderEmpty()
    {
        $mimeType = new mimetypes\Json();
        $this->mockAcceptHeader(null);
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->with($this->equalTo('example\SpecialJsonFormatter'))
                           ->will($this->returnValue($mimeType));
        $this->assertSame(
                $mimeType,
                $this->responseNegotiator->negotiateMimeType(
                        $this->mockRequest,
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml'],
                                ['application/json' => 'example\SpecialJsonFormatter']
                        )
                )->mimeType()
        );
    }
}

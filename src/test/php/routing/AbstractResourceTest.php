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
use stubbles\input\ValueReader;
use stubbles\peer\http\HttpVersion;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\auth\AuthHandler;
use stubbles\webapp\response\WebResponse;
use stubbles\webapp\response\mimetypes\Json;
/**
 * Helper class for the test.
 */
class TestAbstractResource extends AbstractResource
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
     * @param   \stubbles\webapp\Request   $request    current request
     * @param   \stubbles\webapp\Response  $response   response to send
     * @return  bool
     */
    public function data(Request $request, Response $response) {}
}
/**
 * Tests for stubbles\webapp\routing\AbstractResource.
 *
 * @since  2.0.0
 * @group  routing
 */
class AbstractResourceTest extends \PHPUnit_Framework_TestCase
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
     * @type  \stubbles\webapp\response\WebResponse
     */
    private $response;
    /**
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjector;
    /**
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInterceptors;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\webapp\Request');
        $this->mockRequest->expects($this->once())
                          ->method('protocolVersion')
                          ->will($this->returnValue(new HttpVersion(1, 1)));
        $this->response = new WebResponse($this->mockRequest);
        $this->mockInjector = $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock();
        $this->mockInterceptors = $this->getMockBuilder('stubbles\webapp\interceptor\Interceptors')
                                   ->disableOriginalConstructor()
                                   ->getMock();
    }

    /**
     * creates instance to test
     *
     * @param   array     $preInterceptors
     * @param   array     $postInterceptors
     * @return  \stubbles\webapp\routing\AbstractResource
     */
    private function createRoute(SupportedMimeTypes $mimeTypes = null)
    {
        return new TestAbstractResource(
                $this->mockInjector,
                new CalledUri('http://example.com/hello/world', 'GET'),
                $this->mockInterceptors,
                null === $mimeTypes ? new SupportedMimeTypes([]) : $mimeTypes

        );
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        $this->assertEquals(
                'https://example.com/hello/world',
                (string) $this->createRoute()->httpsUri()
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function negotiatesPassThroughIfContentNegotiationDisabled()
    {
        $this->assertTrue(
                $this->createRoute(
                        SupportedMimeTypes::createWithDisabledContentNegotation()
                )->negotiateMimeType(
                        $this->getMock('stubbles\webapp\Request'),
                        $this->response
                )
        );
        $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $this->response->mimeType()
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function negotiatesNothingIfNoMatchCanBeFound()
    {
        $mockRequest = $this->getMock('stubbles\webapp\Request');
        $mockRequest->expects($this->once())
                ->method('readHeader')
                ->with($this->equalTo('HTTP_ACCEPT'))
                ->will($this->returnValue(ValueReader::forValue('text/html')));
        $this->assertFalse(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml']
                        )
                )->negotiateMimeType($mockRequest, $this->response)
        );
        $this->assertEquals(406, $this->response->statusCode());
        $this->assertTrue(
                $this->response->containsHeader(
                        'X-Acceptable',
                        'application/json, application/xml'
                )
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function missingMimeTypeClassForNegotiatedMimeTypeTriggersInternalServerError()
    {
        $mockRequest = $this->getMock('stubbles\webapp\Request');
        $mockRequest->expects($this->once())
                ->method('readHeader')
                ->with($this->equalTo('HTTP_ACCEPT'))
                ->will($this->returnValue(ValueReader::forValue('application/foo')));
        $this->assertFalse(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/foo', 'application/xml']
                        )
                )->negotiateMimeType($mockRequest, $this->response)
        );
        $this->assertEquals(500, $this->response->statusCode());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function createsNegotiatedMimeType()
    {
        $mockRequest = $this->getMock('stubbles\webapp\Request');
        $mockRequest->expects($this->once())
                ->method('readHeader')
                ->with($this->equalTo('HTTP_ACCEPT'))
                ->will($this->returnValue(ValueReader::forValue('application/json')));
        $mimeType = new Json();
        $this->mockInjector->expects($this->once())
                           ->method('getInstance')
                           ->will($this->returnValue($mimeType));
        $this->assertTrue(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml']
                        )
                )->negotiateMimeType($mockRequest, $this->response)
        );
        $this->assertSame(
                $mimeType,
                $this->response->mimeType()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsGivenListOfSupportedMimeTypes()
    {
        $this->assertEquals(
                [],
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
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->response))
                ->will($this->returnValue(true));
        $this->assertTrue(
                $this->createRoute()
                        ->applyPreInterceptors(
                                $this->mockRequest,
                                $this->response
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
                ->with($this->equalTo($this->mockRequest), $this->equalTo($this->response))
                ->will($this->returnValue(true));
        $this->assertTrue(
                $this->createRoute()
                        ->applyPostInterceptors(
                                $this->mockRequest,
                                $this->response
                        )
        );
    }
}

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
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\auth\AuthHandler;
use stubbles\webapp\response\mimetypes\Json;
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
     * @param   \stubbles\webapp\Request   $request    current request
     * @param   \stubbles\webapp\Response  $response   response to send
     * @return  bool
     */
    public function process(Request $request, Response $response) {}
}
/**
 * Tests for stubbles\webapp\routing\AbstractProcessableRoute.
 *
 * @since  2.0.0
 * @group  routing
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
        $this->mockResponse = $this->getMock('stubbles\webapp\Response');
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
     * @return  ProcessableRoute
     */
    private function createRoute(SupportedMimeTypes $mimeTypes = null)
    {
        return new TestAbstractProcessableRoute(
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
        $this->assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $this->createRoute(
                        SupportedMimeTypes::createWithDisabledContentNegotation()
                )->negotiateMimeType($this->getMock('stubbles\webapp\Request'))
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
        $this->assertNull(
                $this->createRoute(new SupportedMimeTypes(['application/json', 'application/xml']))
                        ->negotiateMimeType($mockRequest)
        );
    }

    /**
     * @test
     * @expectedException  RuntimeException
     * @since  6.0.0
     */
    public function missingMimeTypeClassForNegotiatedMimeTypeThrowsRuntimeException()
    {
        $mockRequest = $this->getMock('stubbles\webapp\Request');
        $mockRequest->expects($this->once())
                ->method('readHeader')
                ->with($this->equalTo('HTTP_ACCEPT'))
                ->will($this->returnValue(ValueReader::forValue('application/foo')));
        $this->createRoute(new SupportedMimeTypes(['application/foo', 'application/xml']))
                ->negotiateMimeType($mockRequest);
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
        $this->assertSame(
                $mimeType,
                $this->createRoute(new SupportedMimeTypes(['application/json', 'application/xml']))
                        ->negotiateMimeType($mockRequest)
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
                           ->with($this->equalTo($this->mockRequest), $this->equalTo($this->mockResponse))
                           ->will($this->returnValue(true));
        $this->assertTrue(
                $this->createRoute()
                        ->applyPreInterceptors(
                                $this->mockRequest,
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
        $this->assertTrue(
                $this->createRoute()
                        ->applyPostInterceptors(
                                $this->mockRequest,
                                $this->mockResponse
                        )
        );
    }
}

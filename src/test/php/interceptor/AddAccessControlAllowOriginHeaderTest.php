<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\interceptor;
use stubbles\input\ValueReader;
use stubbles\lang;
/**
 * Tests for stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader.
 *
 * @since  3.4.0
 * @group  interceptor
 */
class AddAccessControlAllowOriginHeaderTest extends \PHPUnit_Framework_TestCase
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
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('stubbles\webapp\response\Response');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $constructor = lang\reflectConstructor('stubbles\webapp\interceptor\AddAccessControlAllowOriginHeader');
        $this->assertTrue($constructor->hasAnnotation('Inject'));
        $this->assertTrue($constructor->hasAnnotation('Property'));
        $this->assertEquals(
                'stubbles.webapp.origin.hosts',
                $constructor->annotation('Property')->getValue()
        );
    }

    public function emptyConfigs()
    {
        return [[null], [''], [[]]];
    }

    /**
     * @test
     * @dataProvider  emptyConfigs
     */
    public function doesNotAddHeaderWhenNoAllowedOriginHostConfigured($emptyConfig)
    {
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $addAccessControlAllowOriginHeader = new AddAccessControlAllowOriginHeader($emptyConfig);
        $addAccessControlAllowOriginHeader->postProcess($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenRequestContainsNoOriginHeader()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasHeader')
                          ->will($this->returnValue(false));
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $addAccessControlAllowOriginHeader = new AddAccessControlAllowOriginHeader('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        $addAccessControlAllowOriginHeader->postProcess($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function doesNotAddHeaderWhenOriginFromRequestDoesNotMatchAllowedOriginHosts()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readHeader')
                          ->will($this->returnValue(ValueReader::forValue('http://example.net')));
        $this->mockResponse->expects($this->never())
                           ->method('addHeader');
        $addAccessControlAllowOriginHeader = new AddAccessControlAllowOriginHeader('^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        $addAccessControlAllowOriginHeader->postProcess($this->mockRequest, $this->mockResponse);
    }

    /**
     * @test
     */
    public function addsHeaderWhenOriginFromRequestIsAllowed()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readHeader')
                          ->will($this->returnValue(ValueReader::forValue('http://foo.example.com:9039')));
        $this->mockResponse->expects($this->once())
                           ->method('addHeader')
                           ->with(
                                $this->equalTo('Access-Control-Allow-Origin'),
                                $this->equalTo('http://foo.example.com:9039')
                             );
        $addAccessControlAllowOriginHeader = new AddAccessControlAllowOriginHeader('^http://[a-zA-Z0-9-\.]+example\.net(:[0-9]{4})?$|^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$');
        $addAccessControlAllowOriginHeader->postProcess($this->mockRequest, $this->mockResponse);
    }
}

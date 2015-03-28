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
use stubbles\webapp\response\Error;
/**
 * Tests for stubbles\webapp\routing\MethodNotAllowed.
 *
 * @since  2.2.0
 * @group  routing
 */
class MethodNotAllowedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\MethodNotAllowed
     */
    private $methodNotAllowed;
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
        $this->methodNotAllowed = new MethodNotAllowed(
                $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new CalledUri('http://example.com/hello/world', 'GET'),
                $this->getMockBuilder('stubbles\webapp\routing\Interceptors')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new SupportedMimeTypes([]),
                ['GET', 'POST', 'HEAD']
        );
        $this->mockRequest  = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse = $this->getMock('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        $this->assertFalse($this->methodNotAllowed->requiresHttps());
    }

    /**
     * @test
     */
    public function triggers405MethodNotAllowedResponse()
    {
        $error = Error::methodNotAllowed(
                'DELETE',
                ['GET', 'POST', 'HEAD', 'OPTIONS']
        );
        $this->mockRequest->expects($this->once())
                ->method('method')
                ->will($this->returnValue('DELETE'));
        $this->mockResponse->expects($this->once())
                ->method('methodNotAllowed')
                ->with(
                        $this->equalTo('DELETE'),
                        $this->equalTo(['GET', 'POST', 'HEAD', 'OPTIONS'])
                 )
                 ->will($this->returnValue($error));
        $this->assertSame(
                $error,
                $this->methodNotAllowed->resolve(
                        $this->mockRequest,
                        $this->mockResponse
                )
        );
    }
}
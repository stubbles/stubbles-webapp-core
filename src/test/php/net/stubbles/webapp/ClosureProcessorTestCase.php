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
use net\stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
/**
 * Tests for net\stubbles\webapp\ClosureProcessor.
 *
 * @since  2.0.0
 * @group  core
 */
class ClosureProcessorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ClosureProcessor
     */
    private $closureProcessor;
    /**
     * mocked request
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockRequest  = $this->getMock('net\stubbles\input\web\WebRequest');
        $this->mockResponse = $this->getMock('net\stubbles\webapp\response\Response');
        $this->closureProcessor = new ClosureProcessor(function(WebRequest $request, Response $response)
                                                       {
                                                           $request->cancel();
                                                           $response->setStatusCode(418);
                                                       },
                                                       $this->mockRequest,
                                                       $this->mockResponse
                                  );
    }

    /**
     * @test
     */
    public function neverRequiresSsl()
    {
        $uriRequest = UriRequest::fromString('http://example.net/');
        $this->assertFalse($this->closureProcessor->startup($uriRequest)
                                                  ->requiresSsl($uriRequest)
        );
    }

    /**
     * @test
     */
    public function processCallsClosure()
    {
        $this->mockRequest->expects($this->once())
                          ->method('cancel');
        $this->mockResponse->expects($this->once())
                           ->method('setStatusCode')
                           ->with($this->equalTo(418));
        $this->closureProcessor->startup(UriRequest::fromString('http://example.net/'))
                               ->process()
                               ->cleanup();
    }
}
?>
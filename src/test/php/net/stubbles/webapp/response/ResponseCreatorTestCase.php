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
 * Tests for net\stubbles\webapp\response\ResponseCreator.
 *
 * @group  response
 */
class ResponseCreatorTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function createWithInvalidProtocolCreatesResponseWithStatusCode505()
    {
        $response = ResponseCreator::createForProtocol('foo');
        $this->assertInstanceOf('net\stubbles\webapp\response\WebResponse', $response);
        $this->assertEquals(505, $response->getStatusCode());
        $this->assertEquals('1.1', $response->getVersion());
    }

    /**
     * @test
     */
    public function createWithTooLowProtocolCreatesResponseWithStatusCode505()
    {
        $response = ResponseCreator::createForProtocol('HTTP/0.9');
        $this->assertInstanceOf('net\stubbles\webapp\response\WebResponse', $response);
        $this->assertEquals(505, $response->getStatusCode());
        $this->assertEquals('1.1', $response->getVersion());
    }

    /**
     * @test
     */
    public function createWithTooHighProtocolCreatesResponseWithStatusCode505()
    {
        $response = ResponseCreator::createForProtocol('HTTP/1.2');
        $this->assertInstanceOf('net\stubbles\webapp\response\WebResponse', $response);
        $this->assertEquals(505, $response->getStatusCode());
        $this->assertEquals('1.1', $response->getVersion());
    }

    /**
     * @test
     */
    public function createWithProtocol1_0CreatesResponseWithVersion1_0()
    {
        $response = ResponseCreator::createForProtocol('HTTP/1.0');
        $this->assertInstanceOf('net\stubbles\webapp\response\WebResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('1.0', $response->getVersion());
    }

    /**
     * @test
     */
    public function createWithProtocol1_1CreatesResponseWithVersion1_1()
    {
        $response = ResponseCreator::createForProtocol('HTTP/1.1');
        $this->assertInstanceOf('net\stubbles\webapp\response\WebResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('1.1', $response->getVersion());
    }
}
?>
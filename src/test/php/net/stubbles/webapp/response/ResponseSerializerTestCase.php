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
 * Test for net\stubbles\webapp\response\ResponseSerializer.
 *
 * @since  1.7.0
 * @group  response
 * @group  bug262
 */
class ResponseSerializerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  ResponseSerializer
     */
    private $responseSerializer;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->responseSerializer = new ResponseSerializer();
    }

    /**
     * @test
     */
    public function serializeTurnsResponseIntoStringRepresentation()
    {
        $this->assertInternalType('string', $this->responseSerializer->serialize(new WebResponse()));
    }

    /**
     * @test
     */
    public function serializeWithoutCookieDoesNotContainCookiesInStringRepresentation()
    {
        $response = new WebResponse();
        $response->addCookie(Cookie::create('foo', 'bar'));
        $unserializedResponse = unserialize($this->responseSerializer->serializeWithoutCookies($response));
        $this->assertFalse($unserializedResponse->hasCookie('foo'));
    }

    /**
     * @test
     * @since  1.7.1
     */
    public function serializedResponseWithoutCookiesContainsBody()
    {
        $response = new WebResponse();
        $response->write('foo bar baz');
        $unserializedResponse = unserialize($this->responseSerializer->serializeWithoutCookies($response));
        $this->assertEquals('foo bar baz', $unserializedResponse->getBody());
    }

    /**
     * @test
     * @since  1.7.1
     */
    public function serializedResponseWithoutCookiesContainsHeaders()
    {
        $response = new WebResponse();
        $response->addHeader('foo', 'bar')
                 ->addHeader('other', 'baz');
        $unserializedResponse = unserialize($this->responseSerializer->serializeWithoutCookies($response));
        $this->assertTrue($unserializedResponse->hasHeader('foo'));
        $this->assertEquals('bar', $unserializedResponse->getHeader('foo'));
        $this->assertTrue($unserializedResponse->hasHeader('other'));
        $this->assertEquals('baz', $unserializedResponse->getHeader('other'));
    }

    /**
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     */
    public function unserializingInvalidSerializedResponseThrowsIllegalArgumentException()
    {
        $this->responseSerializer->unserialize('invalid');
    }

    /**
     * @test
     */
    public function unserializeReturnsResponseInstance()
    {
        $this->assertInstanceOf('net\\stubbles\\webapp\\response\\WebResponse',
                                $this->responseSerializer->unserialize(serialize(new WebResponse()))
        );
    }
}
?>
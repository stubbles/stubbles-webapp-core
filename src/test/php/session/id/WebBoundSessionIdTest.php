<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session\id;
use stubbles\input\ValueReader;
/**
 * Tests for stubbles\webapp\session\id\WebBoundSessionId.
 *
 * @since  2.0.0
 * @group  session
 * @group  id
 */
class WebBoundSessionIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\session\id\WebBoundSessionId
     */
    private $webBoundSessionId;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockWebRequest;
    /**
     * mocked responsr instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->mockWebRequest    = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse      = $this->getMock('stubbles\webapp\Response');
        $this->webBoundSessionId = new WebBoundSessionId(
                $this->mockWebRequest,
                $this->mockResponse,
                'foo'
        );
    }

    /**
     * @test
     */
    public function returnsGivenSessionName()
    {
        assertEquals('foo', $this->webBoundSessionId->name());
    }

    /**
     * @test
     */
    public function createsSessionIdIfNotInRequest()
    {
        $this->mockWebRequest->expects($this->once())
                             ->method('hasParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(false));
        $this->mockWebRequest->expects($this->once())
                             ->method('hasCookie')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(false));
        assertRegExp(
                '/^([a-zA-Z0-9]{32})$/D',
                (string) $this->webBoundSessionId
        );
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestParamInvalid()
    {
        $this->mockWebRequest->expects($this->once())
                             ->method('hasParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(true));
        $this->mockWebRequest->expects($this->once())
                             ->method('readParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(ValueReader::forValue('invalid')));
        assertRegExp(
                '/^([a-zA-Z0-9]{32})$/D',
                (string) $this->webBoundSessionId
        );
    }

    /**
     * @test
     */
    public function usesParamSessionIdIfRequestParamValid()
    {
        $this->mockWebRequest->expects($this->once())
                             ->method('hasParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(true));
        $this->mockWebRequest->expects($this->once())
                             ->method('readParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(ValueReader::forValue('abcdefghij1234567890abcdefghij12')));
        assertEquals(
                'abcdefghij1234567890abcdefghij12',
                (string) $this->webBoundSessionId
        );
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestCookieInvalid()
    {
        $this->mockWebRequest->expects($this->once())
                             ->method('hasParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(false));
        $this->mockWebRequest->expects($this->once())
                             ->method('hasCookie')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(true));
        $this->mockWebRequest->expects($this->once())
                             ->method('readCookie')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(ValueReader::forValue('invalid')));
        assertRegExp(
                '/^([a-zA-Z0-9]{32})$/D',
                (string) $this->webBoundSessionId
        );
    }

    /**
     * @test
     */
    public function usesCookieSessionIdIfRequestCookieValid()
    {
        $this->mockWebRequest->expects($this->once())
                             ->method('hasParam')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(false));
        $this->mockWebRequest->expects($this->once())
                             ->method('hasCookie')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(true));
        $this->mockWebRequest->expects($this->once())
                             ->method('readCookie')
                             ->with($this->equalTo('foo'))
                             ->will($this->returnValue(ValueReader::forValue('abcdefghij1234567890abcdefghij12')));
        assertEquals(
                'abcdefghij1234567890abcdefghij12',
                (string) $this->webBoundSessionId
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId()
    {
        $previous = (string) $this->webBoundSessionId;
        assertNotEquals(
                $previous,
                (string) $this->webBoundSessionId->regenerate()
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid()
    {
        assertRegExp(
                '/^([a-zA-Z0-9]{32})$/D',
                (string) $this->webBoundSessionId->regenerate()
        );
    }

    /**
     * @test
     */
    public function regenerateStoresNewSessionIdInCookie()
    {
        $this->mockResponse->expects($this->once())
                           ->method('addCookie');
        $this->webBoundSessionId->regenerate();
    }

    /**
     * @test
     */
    public function invalidateRemovesSessionidCookie()
    {
        $this->mockResponse->expects($this->once())
                           ->method('removeCookie')
                           ->with($this->equalTo('foo'));
        assertSame(
                $this->webBoundSessionId,
                $this->webBoundSessionId->invalidate()
        );
    }
}

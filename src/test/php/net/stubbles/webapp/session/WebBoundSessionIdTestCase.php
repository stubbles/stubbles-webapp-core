<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
use stubbles\input\ValueReader;
/**
 * Tests for net\stubbles\webapp\session\WebBoundSessionId.
 *
 * @since  2.0.0
 * @group  session
 */
class WebBoundSessionIdTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  WebBoundSessionId
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
        $this->mockWebRequest    = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse      = $this->getMock('net\stubbles\webapp\response\Response');
        $this->webBoundSessionId = new WebBoundSessionId($this->mockWebRequest,
                                                         $this->mockResponse,
                                                         'foo'
                                   );
    }

    /**
     * @test
     */
    public function returnsGivenSessionName()
    {
        $this->assertEquals('foo', $this->webBoundSessionId->getName());
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
        $this->assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            $this->webBoundSessionId->get()
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
        $this->assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            $this->webBoundSessionId->get()
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
        $this->assertEquals('abcdefghij1234567890abcdefghij12',
                            $this->webBoundSessionId->get()
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
        $this->assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            $this->webBoundSessionId->get()
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
        $this->assertEquals('abcdefghij1234567890abcdefghij12',
                            $this->webBoundSessionId->get()
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId()
    {
        $previous = $this->webBoundSessionId->get();
        $this->assertNotEquals($previous,
                               $this->webBoundSessionId->regenerate()
                                                       ->get()
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid()
    {
        $this->assertRegExp('/^([a-zA-Z0-9]{32})$/D',
                            $this->webBoundSessionId->regenerate()
                                                    ->get()
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
        $this->assertSame($this->webBoundSessionId,
                          $this->webBoundSessionId->invalidate()
        );
    }
}

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
    private $request;
    /**
     * mocked responsr instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->request  = $this->getMock('stubbles\webapp\Request');
        $this->response = $this->getMock('stubbles\webapp\Response');
        $this->webBoundSessionId = new WebBoundSessionId(
                $this->request,
                $this->response,
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
        $this->request->expects(once())
                ->method('hasParam')
                ->with(equalTo('foo'))
                ->will(returnValue(false));
        $this->request->expects(once())
                ->method('hasCookie')
                ->with(equalTo('foo'))
                ->will(returnValue(false));
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
        $this->request->expects(once())
                ->method('hasParam')
                ->with(equalTo('foo'))
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readParam')
                ->with(equalTo('foo'))
                ->will(returnValue(ValueReader::forValue('invalid')));
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
        $this->request->expects(once())
                ->method('hasParam')
                ->with(equalTo('foo'))
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readParam')
                ->with(equalTo('foo'))
                ->will(returnValue(ValueReader::forValue('abcdefghij1234567890abcdefghij12')));
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
        $this->request->expects(once())
                ->method('hasParam')
                ->with(equalTo('foo'))
                ->will(returnValue(false));
        $this->request->expects(once())
                ->method('hasCookie')
                ->with(equalTo('foo'))
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readCookie')
                ->with(equalTo('foo'))
                ->will(returnValue(ValueReader::forValue('invalid')));
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
        $this->request->expects(once())
                ->method('hasParam')
                ->with(equalTo('foo'))
                ->will(returnValue(false));
        $this->request->expects(once())
                ->method('hasCookie')
                ->with(equalTo('foo'))
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readCookie')
                ->with(equalTo('foo'))
                ->will(returnValue(ValueReader::forValue('abcdefghij1234567890abcdefghij12')));
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
        $this->response->expects(once())->method('addCookie');
        $this->webBoundSessionId->regenerate();
    }

    /**
     * @test
     */
    public function invalidateRemovesSessionidCookie()
    {
        $this->response->expects(once())
                ->method('removeCookie')
                ->with(equalTo('foo'));
        assertSame(
                $this->webBoundSessionId,
                $this->webBoundSessionId->invalidate()
        );
    }
}

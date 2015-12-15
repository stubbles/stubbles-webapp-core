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
use bovigo\callmap\NewInstance;
use stubbles\input\ValueReader;
use stubbles\webapp\Request;
use stubbles\webapp\Response;

use function bovigo\callmap\verify;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $request;
    /**
     * mocked responsr instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $response;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
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
        $this->request->mapCalls(['hasParam' => false, 'hasCookie' => false]);
        assertRegExp(
                '/^([a-zA-Z0-9]{32})$/D',
                (string) $this->webBoundSessionId
        );
    }

    /**
     * @test
     */
    public function usesSessionIdNameForRequestValues()
    {
        $this->request->mapCalls(['hasParam' => false, 'hasCookie' => false]);
        (string) $this->webBoundSessionId;
        verify($this->request, 'hasParam')->received('foo');
        verify($this->request, 'hasCookie')->received('foo');
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestParamInvalid()
    {
        $this->request->mapCalls(
                ['hasParam'  => true,
                 'readParam' => ValueReader::forValue('invalid')
                ]
        );
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
        $this->request->mapCalls(
                ['hasParam'  => true,
                 'readParam' => ValueReader::forValue('abcdefghij1234567890abcdefghij12')
                ]
        );
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
        $this->request->mapCalls(
                ['hasParam'   => false,
                 'hasCookie'  => true,
                 'readCookie' => ValueReader::forValue('invalid')
                ]
        );
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
        $this->request->mapCalls(
                ['hasParam'   => false,
                 'hasCookie'  => true,
                 'readCookie' => ValueReader::forValue('abcdefghij1234567890abcdefghij12')
                ]
        );
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
        $this->webBoundSessionId->regenerate();
        verify($this->response, 'addCookie')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function invalidateRemovesSessionidCookie()
    {
        assertSame(
                $this->webBoundSessionId,
                $this->webBoundSessionId->invalidate()
        );
        verify($this->response, 'removeCookie')->received('foo');
    }
}

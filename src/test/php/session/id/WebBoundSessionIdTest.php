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
        $this->request  = NewInstance::of('stubbles\webapp\Request');
        $this->response = NewInstance::of('stubbles\webapp\Response');
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
        assertEquals(['foo'], $this->request->argumentsReceivedFor('hasParam'));
        assertEquals(['foo'], $this->request->argumentsReceivedFor('hasCookie'));
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
        assertEquals(1, $this->response->callsReceivedFor('addCookie'));
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
        assertEquals(['foo'], $this->response->argumentsReceivedFor('removeCookie'));
    }
}

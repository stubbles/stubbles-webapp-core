<?php
declare(strict_types=1);
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

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isNotEqualTo;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\assert\predicate\matches;
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
        assert($this->webBoundSessionId->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function createsSessionIdIfNotInRequest()
    {
        $this->request->returns(['hasParam' => false, 'hasCookie' => false]);
        assert(
                (string) $this->webBoundSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function usesSessionIdNameForRequestValues()
    {
        $this->request->returns(['hasParam' => false, 'hasCookie' => false]);
        (string) $this->webBoundSessionId;
        verify($this->request, 'hasParam')->received('foo');
        verify($this->request, 'hasCookie')->received('foo');
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestParamInvalid()
    {
        $this->request->returns(
                ['hasParam'  => true,
                 'readParam' => ValueReader::forValue('invalid')
                ]
        );
        assert(
                (string) $this->webBoundSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function usesParamSessionIdIfRequestParamValid()
    {
        $this->request->returns(
                ['hasParam'  => true,
                 'readParam' => ValueReader::forValue('abcdefghij1234567890abcdefghij12')
                ]
        );
        assert(
                (string) $this->webBoundSessionId,
                equals('abcdefghij1234567890abcdefghij12')
        );
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestCookieInvalid()
    {
        $this->request->returns(
                ['hasParam'   => false,
                 'hasCookie'  => true,
                 'readCookie' => ValueReader::forValue('invalid')
                ]
        );
        assert(
                (string) $this->webBoundSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function usesCookieSessionIdIfRequestCookieValid()
    {
        $this->request->returns([
                'hasParam'   => false,
                'hasCookie'  => true,
                'readCookie' => ValueReader::forValue('abcdefghij1234567890abcdefghij12')
        ]);
        assert(
                (string) $this->webBoundSessionId,
                equals('abcdefghij1234567890abcdefghij12')
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId()
    {
        $this->request->returns(['hasParam' => false, 'hasCookie' => false]);
        $previous = (string) $this->webBoundSessionId;
        assert(
                (string) $this->webBoundSessionId->regenerate(),
                isNotEqualTo($previous)
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid()
    {
        assert(
                (string) $this->webBoundSessionId->regenerate(),
                matches('/^([a-zA-Z0-9]{32})$/D')
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
        assert(
                $this->webBoundSessionId->invalidate(),
                isSameAs($this->webBoundSessionId)
        );
        verify($this->response, 'removeCookie')->received('foo');
    }
}

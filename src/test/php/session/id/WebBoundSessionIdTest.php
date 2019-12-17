<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session\id;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\input\ValueReader;
use stubbles\webapp\Request;
use stubbles\webapp\Response;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertTrue;
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
class WebBoundSessionIdTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\session\id\WebBoundSessionId
     */
    private $webBoundSessionId;
    /**
     * @var  Request&\bovigo\callmap\ClassProxy
     */
    private $request;
    /**
     * @var  Response&\bovigo\callmap\ClassProxy
     */
    private $response;

    protected function setUp(): void
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
    public function returnsGivenSessionName(): void
    {
        assertThat($this->webBoundSessionId->name(), equals('foo'));
    }

    /**
     * @test
     */
    public function createsSessionIdIfNotInRequest(): void
    {
        $this->request->returns(['hasParam' => false, 'hasCookie' => false]);
        assertThat(
                (string) $this->webBoundSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function usesSessionIdNameForRequestValues(): void
    {
        $this->request->returns(['hasParam' => false, 'hasCookie' => false]);
        $this->webBoundSessionId->__toString();
        verify($this->request, 'hasParam')->received('foo');
        verify($this->request, 'hasCookie')->received('foo');
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestParamInvalid(): void
    {
        $this->request->returns(
                ['hasParam'  => true,
                 'readParam' => ValueReader::forValue('invalid')
                ]
        );
        assertThat(
                (string) $this->webBoundSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function usesParamSessionIdIfRequestParamValid(): void
    {
        $this->request->returns(
                ['hasParam'  => true,
                 'readParam' => ValueReader::forValue('abcdefghij1234567890abcdefghij12')
                ]
        );
        assertThat(
                (string) $this->webBoundSessionId,
                equals('abcdefghij1234567890abcdefghij12')
        );
    }

    /**
     * @test
     */
    public function createsSessionIdIfRequestCookieInvalid(): void
    {
        $this->request->returns(
                ['hasParam'   => false,
                 'hasCookie'  => true,
                 'readCookie' => ValueReader::forValue('invalid')
                ]
        );
        assertThat(
                (string) $this->webBoundSessionId,
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function usesCookieSessionIdIfRequestCookieValid(): void
    {
        $this->request->returns([
                'hasParam'   => false,
                'hasCookie'  => true,
                'readCookie' => ValueReader::forValue('abcdefghij1234567890abcdefghij12')
        ]);
        assertThat(
                (string) $this->webBoundSessionId,
                equals('abcdefghij1234567890abcdefghij12')
        );
    }

    /**
     * @test
     */
    public function regenerateChangesSessionId(): void
    {
        $this->request->returns(['hasParam' => false, 'hasCookie' => false]);
        $previous = (string) $this->webBoundSessionId;
        assertThat(
                (string) $this->webBoundSessionId->regenerate(),
                isNotEqualTo($previous)
        );
    }

    /**
     * @test
     */
    public function regeneratedSessionIdIsValid(): void
    {
        assertThat(
                (string) $this->webBoundSessionId->regenerate(),
                matches('/^([a-zA-Z0-9]{32})$/D')
        );
    }

    /**
     * @test
     */
    public function regenerateStoresNewSessionIdInCookie(): void
    {
        $this->webBoundSessionId->regenerate();
        assertTrue(verify($this->response, 'addCookie')->wasCalledOnce());
    }

    /**
     * @test
     */
    public function invalidateRemovesSessionidCookie(): void
    {
        assertThat(
                $this->webBoundSessionId->invalidate(),
                isSameAs($this->webBoundSessionId)
        );
        assertTrue(verify($this->response, 'removeCookie')->received('foo'));
    }
}

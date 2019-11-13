<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\request;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\input\{ValueReader, ValueValidator, errors\ParamErrors};
use stubbles\peer\{IpAddress, MalformedUri, http\HttpVersion};
use stubbles\streams\InputStream;
use stubbles\webapp\auth\{Identity, Roles, User};
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertNull,
    assertTrue,
    expect,
    predicate\equals,
    predicate\isInstanceOf,
    predicate\isNotEqualTo,
    predicate\isOfSize,
    predicate\isSameAs
};
/**
 * Tests for stubbles\webapp\request\WebRequest.
 *
 * @group  request
 */
class WebRequestTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\request\WebRequest
     */
    private $webRequest;
    /**
     * backup of globals $_GET, $_POST, $_SERVER, $COOKIE
     *
     * @type  array
     */
    private $globals;

    protected function setUp(): void
    {
        $this->globals        = ['GET'    => $_GET,
                                 'POST'   => $_POST,
                                 'SERVER' => $_SERVER,
                                 'COOKIE' => $_COOKIE

                                ];
        $this->webRequest = $this->createBaseWebRequest(
                ['foo' => 'bar', 'roland' => 'TB-303'],
                ['HTTP_ACCEPT' => 'text/html', 'REQUEST_METHOD' => 'post'],
                ['chocolateChip' => 'Omnomnomnom', 'master' => 'servant']
        );
    }

    protected function tearDown(): void
    {
        $_GET    = $this->globals['GET'];
        $_POST   = $this->globals['POST'];
        $_SERVER = $this->globals['SERVER'];
        $_COOKIE = $this->globals['COOKIE'];
    }

    private function createBaseWebRequest(
            array $params  = [],
            array $headers = [],
            array $cookies = []
    ): WebRequest {
        return new WebRequest($params, $headers, $cookies);
    }

    private function fillGlobals(string $requestMethod = 'GET')
    {
        $_GET    = ['foo' => 'bar', 'roland' => 'TB-303'];
        $_POST   = ['baz' => 'blubb', 'donald' => '313'];
        $_SERVER = ['REQUEST_METHOD' => $requestMethod, 'HTTP_ACCEPT' => 'text/html'];
        $_COOKIE = ['chocolateChip'  => 'Omnomnomnom', 'master' => 'servant'];
    }

    /**
     * @test
     */
    public function usesGetParamsFromRawSourceWhenRequestMethodIsGET()
    {
        $this->fillGlobals('GET');
        assertThat(
                WebRequest::fromRawSource()->paramNames(),
                equals(['foo', 'roland'])
        );
    }

    /**
     * @test
     */
    public function usesPostParamsFromRawSourceWhenRequestMethodIsPOST()
    {
        $this->fillGlobals('POST');
        assertThat(
                WebRequest::fromRawSource()->paramNames(),
                equals(['baz', 'donald'])
        );
    }

    /**
     * @test
     */
    public function usesServerForHeaderFromRawSource()
    {
        $this->fillGlobals();
        assertThat(
                WebRequest::fromRawSource()->headerNames(),
                equals(['REQUEST_METHOD', 'HTTP_ACCEPT'])
        );
    }

    /**
     * @test
     */
    public function usesCookieForCookieFromRawSource()
    {
        $this->fillGlobals();
        assertThat(
                WebRequest::fromRawSource()->cookieNames(),
                equals(['chocolateChip', 'master'])
        );
    }

    /**
     * @test
     */
    public function returnsRequestMethodInUpperCase()
    {
        assertThat($this->webRequest->method(), equals('POST'));
    }

    /**
     * @test
     */
    public function sslCheckReturnsTrueIfHttpsSet()
    {
        assertTrue(
                $this->createBaseWebRequest([], ['HTTPS' => true])->isSsl()
        );
    }

    /**
     * @test
     */
    public function sslCheckReturnsFalseIfHttpsNotSet()
    {
        assertFalse(
                $this->createBaseWebRequest([], ['HTTPS' => null])->isSsl()
        );
    }

    /**
     * @since  2.0.2
     * @test
     */
    public function reportsVersion1_0WhenNoServerProtocolSet()
    {
         assertThat(
                $this->createBaseWebRequest([], [])->protocolVersion(),
                equals(HttpVersion::HTTP_1_0)
        );
    }

    /**
     * @since  2.0.2
     * @test
     */
    public function reportsNullWhenServerProtocolContainsInvalidVersion()
    {
         assertNull(
                $this->createBaseWebRequest([], ['SERVER_PROTOCOL' => 'foo'])
                        ->protocolVersion()
        );
    }

    public function protocolVersions(): array
    {
        return [
            ['HTTP/0.9', '0.9'],
            ['HTTP/1.0', '1.0'],
            ['HTTP/1.1', '1.1'],
            ['HTTP/1.2', '1.2'],
            ['HTTP/1.12', '1.12'],
            ['HTTP/2.0', '2.0'],
        ];
    }

    /**
     * @since  3.0.0
     * @test
     * @dataProvider  protocolVersions
     */
    public function reportsParsedProtocolVersion(string $protocol)
    {
         assertThat(
                $this->createBaseWebRequest([], ['SERVER_PROTOCOL' => $protocol])
                        ->protocolVersion(),
                equals(HttpVersion::fromString($protocol))
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAdressIsNullWhenAccordingHeadersNotPresent()
    {
        assertNull($this->createBaseWebRequest()->originatingIpAddress());
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAdressIsNullWhenRemoteAddressSyntacticallyInvalidAndNoForwardedForHeaderPresent()
    {
        assertNull(
                $this->createBaseWebRequest([], ['REMOTE_ADDR' => 'foo'])
                    ->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAdressIsNullWhenForwardedForHeaderSyntacticallyInvalid()
    {
        assertNull(
                $this->createBaseWebRequest(
                        [],
                        ['REMOTE_ADDR'          => '127.0.0.1',
                         'HTTP_X_FORWARDED_FOR' => 'foo'
                        ]
                       )
                    ->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsRemoteAddressWhenNoForwardedForHeaderPresent()
    {
        assertThat(
                $this->createBaseWebRequest([], ['REMOTE_ADDR' => '127.0.0.1'])
                    ->originatingIpAddress(),
                equals('127.0.0.1')
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsInstanceOfIpAddress()
    {
        assertThat(
                $this->createBaseWebRequest([], ['REMOTE_ADDR' => '127.0.0.1'])
                    ->originatingIpAddress(),
                isInstanceOf(IpAddress::class)
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsForwardedAddressWhenForwardedForHeaderPresent()
    {
        assertThat(
                $this->createBaseWebRequest(
                        [],
                        ['REMOTE_ADDR'          => '127.0.0.1',
                         'HTTP_X_FORWARDED_FOR' => '172.19.120.122'
                        ]
                       )
                    ->originatingIpAddress(),
                equals('172.19.120.122')
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsFirstFromForwardedAddressesWhenForwardedForHeaderContainsList()
    {
        assertThat(
                $this->createBaseWebRequest(
                        [],
                        ['REMOTE_ADDR'          => '127.0.0.1',
                         'HTTP_X_FORWARDED_FOR' => '172.19.120.122, 168.30.48.124'
                        ]
                       )
                    ->originatingIpAddress(),
                equals('172.19.120.122')
        );
    }

    /**
     * @test
     */
    public function uriThrowsMalformedUriExceptionOnInvalidRequestUri()
    {
        expect(function() { $this->createBaseWebRequest([], [])->uri(); })
                ->throws(MalformedUri::class);
    }

    /**
     * @test
     */
    public function uriReturnsCompleteRequestUri()
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'stubbles.net',
                         'SERVER_PORT' => 80,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                    )
                    ->uri()
                    ->asString(),
                equals('http://stubbles.net:80/index.php?foo=bar')
        );
    }

    /**
     * @test
     * @since  2.3.2
     */
    public function uriReturnsCompleteRequestUriWithoutDoublePortIfPortIsInHost()
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'localhost:8080',
                         'SERVER_PORT' => 80,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                    )
                    ->uri()
                    ->asString(),
                equals('http://localhost:8080/index.php?foo=bar')
        );
    }

    /**
     * @test
     * @since  2.3.2
     */
    public function uriReturnsCompleteRequestUriWithNonDefaultPort()
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'example.net',
                         'SERVER_PORT' => 8080,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                    )
                    ->uri()
                    ->asString(),
                equals('http://example.net:8080/index.php?foo=bar')
        );
    }

    /**
     * @test
     */
    public function uriReturnsCompleteRequestUriForHttps()
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => true,
                         'HTTP_HOST'   => 'stubbles.net',
                         'SERVER_PORT' => 443,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                    )
                    ->uri()
                    ->asString(),
                equals('https://stubbles.net:443/index.php?foo=bar')
        );
    }

    /**
     * @test
     */
    public function returnsListOfParamNames()
    {
        assertThat($this->webRequest->paramNames(), equals(['foo', 'roland']));
    }

    /**
     * @test
     */
    public function returnsFalseOnCheckForNonExistingParam()
    {
        assertFalse($this->webRequest->hasParam('baz'));
    }

    /**
     * @test
     */
    public function returnsTrueOnCheckForExistingParam()
    {
        assertTrue($this->webRequest->hasParam('foo'));
    }

    /**
     * @test
     */
    public function validateParamReturnsValueValidator()
    {
        assertThat(
                $this->webRequest->validateParam('foo'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function validateParamReturnsValueValidatorForNonExistingParam()
    {
        assertThat(
                $this->webRequest->validateParam('baz'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function readParamReturnsValueReader()
    {
        assertThat(
                $this->webRequest->readParam('foo'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function readParamReturnsValueReaderForNonExistingParam()
    {
        assertThat(
                $this->webRequest->readParam('baz'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function returnsListOfHeaderNames()
    {
        assertThat(
                $this->webRequest->headerNames(),
                equals(['HTTP_ACCEPT', 'REQUEST_METHOD'])
        );
    }

    /**
     * @test
     */
    public function returnsHeaderErrors()
    {
        assertThat(
                $this->webRequest->headerErrors(),
                isInstanceOf(ParamErrors::class)
        );
    }

    /**
     * @test
     */
    public function returnsFalseOnCheckForNonExistingHeader()
    {
        assertFalse($this->webRequest->hasHeader('baz'));
    }

    /**
     * @test
     */
    public function returnsTrueOnCheckForExistingHeader()
    {
        assertTrue($this->webRequest->hasHeader('HTTP_ACCEPT'));
    }

    /**
     * @test
     * @since  3.1.1
     */
    public function returnsFalseOnCheckForRedirectHeaderWhenBothRedirectAndCurrentDoNotExist()
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertFalse($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    /**
     * @test
     * @since  3.1.1
     */
    public function returnsTrueOnCheckForRedirectHeaderWhenRedirectDoesNotButCurrentDoesExist()
    {
        $webRequest = $this->createBaseWebRequest(
                [],
                ['HTTP_AUTHORIZATION'          => 'someCoolToken']
        );
        assertTrue($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    /**
     * @test
     * @since  3.1.1
     */
    public function returnsTrueOnCheckForRedirectHeaderWhenBothRedirectAndCurrentExist()
    {
        $webRequest = $this->createBaseWebRequest(
                [],
                ['HTTP_AUTHORIZATION'          => 'someCoolToken',
                 'REDIRECT_HTTP_AUTHORIZATION' => 'realToken'
                ]
        );
        assertTrue($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    /**
     * @test
     */
    public function validateHeaderReturnsValueValidator()
    {
        assertThat(
                $this->webRequest->validateHeader('HTTP_ACCEPT'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function validateHeaderReturnsValueValidatorForNonExistingParam()
    {
        assertThat(
                $this->webRequest->validateHeader('baz'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  redirect_header
     */
    public function validateRedirectHeaderReturnsValueValidatorForNonExistingHeader()
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertThat(
                $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  redirect_header
     */
    public function validateRedirectHeaderReturnsValueValidatorWithOriginalHeaderIfRedirectHeaderNotPresent()
    {
        $webRequest = $this->createBaseWebRequest([], ['HTTP_AUTHORIZATION' => 'someCoolToken']);
        assertTrue(
                $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION')
                        ->isEqualTo('someCoolToken')
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  redirect_header
     */
    public function validateRedirectHeaderReturnsValueValidatorWithRedirectHeaderIfRedirectHeaderPresent()
    {
        $webRequest = $this->createBaseWebRequest(
                [],
                ['HTTP_AUTHORIZATION'          => 'someCoolToken',
                 'REDIRECT_HTTP_AUTHORIZATION' => 'realToken'
                ]
        );
        assertTrue(
                $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION')
                        ->isEqualTo('realToken')
        );
    }

    /**
     * @test
     */
    public function readHeaderReturnsValueReader()
    {
        assertThat(
                $this->webRequest->readHeader('HTTP_ACCEPT'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function readHeaderReturnsValueReaderForNonExistingParam()
    {
        assertThat(
                $this->webRequest->readHeader('baz'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  redirect_header
     */
    public function readRedirectHeaderReturnsValueReaderForNonExistingHeader()
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertNull(
                $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure()
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  redirect_header
     */
    public function readRedirectHeaderReturnsValueReaderWithOriginalHeaderIfRedirectHeaderNotPresent()
    {
        $webRequest = $this->createBaseWebRequest([], ['HTTP_AUTHORIZATION' => 'someCoolToken']);
        assertThat(
                $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure(),
                equals('someCoolToken')
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  redirect_header
     */
    public function readRedirectHeaderReturnsValueReaderWithRedirectHeaderIfRedirectHeaderPresent()
    {
        $webRequest = $this->createBaseWebRequest(
                [],
                ['HTTP_AUTHORIZATION'          => 'someCoolToken',
                 'REDIRECT_HTTP_AUTHORIZATION' => 'realToken'
                ]
        );
        assertThat(
                $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure(),
                equals('realToken')
        );
    }

    /**
     * @test
     */
    public function returnsListOfCookieNames()
    {
        assertThat($this->webRequest->cookieNames(), equals(['chocolateChip', 'master']));
    }

    /**
     * @test
     */
    public function returnsCookieErrors()
    {
        assertThat(
                $this->webRequest->cookieErrors(),
                isInstanceOf(ParamErrors::class)
        );
    }

    /**
     * @test
     */
    public function returnsFalseOnCheckForNonExistingCookie()
    {
        assertFalse($this->webRequest->hasCookie('baz'));
    }

    /**
     * @test
     */
    public function returnsTrueOnCheckForExistingCookie()
    {
        assertTrue($this->webRequest->hasCookie('chocolateChip'));
    }

    /**
     * @test
     */
    public function validateCookieReturnsValueValidator()
    {
        assertThat(
                $this->webRequest->validateCookie('chocolateChip'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function validateCookieReturnsValueValidatorForNonExistingParam()
    {
        assertThat(
                $this->webRequest->validateCookie('baz'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function readCookieReturnsValueReader()
    {
        assertThat(
                $this->webRequest->readCookie('chocolateChip'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function readCookieReturnsValueReaderForNonExistingParam()
    {
        assertThat(
                $this->webRequest->readCookie('baz'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function returnsUserAgent()
    {
        assertThat(
                $this->createBaseWebRequest(
                        [],
                        ['HTTP_USER_AGENT' => 'foo'],
                        ['chocolateChip' => 'someValue']
                )->userAgent(),
                equals(new UserAgent('foo', true))
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function returnsUserAgentWhenHeaderNotPresent()
    {
        assertThat(
                $this->createBaseWebRequest(
                        [],
                        [],
                        ['chocolateChip' => 'someValue']
                )->userAgent(),
                equals(new UserAgent(null, true))
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function userAgentDoesNotAcceptCookiesWhenNoCookiesInRequest()
    {
        assertFalse(
                $this->createBaseWebRequest([], ['HTTP_USER_AGENT' => 'foo'], [])
                     ->userAgent()
                     ->acceptsCookies()
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function userAgentDoesNotRecognizeBotWithoutAdditionalSignature()
    {
        assertFalse(
                $this->createBaseWebRequest([], ['HTTP_USER_AGENT' => 'foo'], [])
                     ->userAgent()
                     ->isBot()
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function userAgentRecognizedAsBotWithDefaultSignatures()
    {
        assertTrue(
                $this->createBaseWebRequest([], ['HTTP_USER_AGENT' => 'Googlebot /v1.1'], [])
                     ->userAgent()
                     ->isBot()
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function userAgentRecognizedAsBotWithAdditionalSignature()
    {
        assertTrue(
                $this->createBaseWebRequest([], ['HTTP_USER_AGENT' => 'foo'], [])
                     ->userAgent(['foo' => '~foo~'])
                     ->isBot()
        );
    }

    /**
     * @test
     * @group  request_id
     * @since  4.2.0
     */
    public function generatesIdIfNoRequestIdHeaderPresent()
    {
        assertThat($this->createBaseWebRequest()->id(), isOfSize(25));
    }

    /**
     * @test
     * @group  request_id
     * @since  4.2.0
     */
    public function generatedIdIsPersistentThroughoutRequest()
    {
        $request = $this->createBaseWebRequest();
        assertThat($request->id(), equals($request->id()));
    }

    public function invalidRequestIdValues(): array
    {
        return [
            ['too-short'],
            [str_pad('too-long', 201, '-')],
            ['invalid character like space'],
            ["valid-but-\n-linebreaks"]
        ];
    }

    /**
     * @test
     * @group  request_id
     * @dataProvider  invalidRequestIdValues
     * @since  4.2.0
     */
    public function generatesIdIfRequestContainsInvalidValue(string $invalidValue)
    {
        assertThat(
                $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $invalidValue])->id(),
                isNotEqualTo($invalidValue)
        );
    }

    public function validRequestIdValues(): array
    {
        return [
            [str_pad('minimum-size', 20, '-')],
            [str_pad('max-size', 200, '-')],
            ['valid-characters-like+and/numbers=21903']
        ];
    }

    /**
     * @test
     * @group  request_id
     * @dataProvider  validRequestIdValues
     * @since  4.2.0
     */
    public function returnsValidValueFromHeader(string $validValue)
    {
        assertThat(
                $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $validValue])->id(),
                equals($validValue)
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function bodyReturnsInputStream()
    {
        assertThat(
                $this->createBaseWebRequest()->body(),
                isInstanceOf(InputStream::class)
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function hasNoSessionAttachedByDefault()
    {
        assertFalse($this->createBaseWebRequest()->hasSessionAttached());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function defaultSessionIsNull()
    {
        assertNull($this->createBaseWebRequest()->attachedSession());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function hasSessionWhenAttached()
    {
        $request = $this->createBaseWebRequest();
        $session = NewInstance::of(Session::class);
        $request->attachSession($session);
        assertTrue($request->hasSessionAttached());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function returnsAttachedSession()
    {
        $request = $this->createBaseWebRequest();
        $session = NewInstance::of(Session::class);
        assertThat(
                $request->attachSession($session),
                isSameAs($request->attachedSession())
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function hasNoIdentityAssociatedByDefault()
    {
        assertFalse($this->createBaseWebRequest()->hasAssociatedIdentity());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function defaultIdentityIsNull()
    {
        assertNull($this->createBaseWebRequest()->identity());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function hasIdentityWhenAssociated()
    {
        $identity = new Identity(NewInstance::of(User::class), Roles::none());
        assertTrue(
                $this->createBaseWebRequest()
                        ->associate($identity)
                        ->hasAssociatedIdentity()
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function returnsAssociatedIdentity()
    {
        $identity = new Identity(NewInstance::of(User::class), Roles::none());
        assertThat(
                $this->createBaseWebRequest()
                        ->associate($identity)
                        ->identity(),
                isSameAs($identity)
        );
    }
}

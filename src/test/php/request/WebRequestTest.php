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
     * @var  \stubbles\webapp\request\WebRequest
     */
    private $webRequest;
    /**
     * backup of globals $_GET, $_POST, $_SERVER, $COOKIE
     *
     * @var  array<string,array<string,string>>
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

    /**
     * @param   array<string,scalar|null>  $params
     * @param   array<string,scalar|null>  $headers
     * @param   array<string,scalar|null>  $cookies
     * @return  WebRequest
     */
    private function createBaseWebRequest(
            array $params  = [],
            array $headers = [],
            array $cookies = []
    ): WebRequest {
        return new WebRequest($params, $headers, $cookies);
    }

    private function fillGlobals(string $requestMethod = 'GET'): void
    {
        $_GET    = ['foo' => 'bar', 'roland' => 'TB-303'];
        $_POST   = ['baz' => 'blubb', 'donald' => '313'];
        $_SERVER = ['REQUEST_METHOD' => $requestMethod, 'HTTP_ACCEPT' => 'text/html'];
        $_COOKIE = ['chocolateChip'  => 'Omnomnomnom', 'master' => 'servant'];
    }

    /**
     * @test
     */
    public function usesGetParamsFromRawSourceWhenRequestMethodIsGET(): void
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
    public function usesPostParamsFromRawSourceWhenRequestMethodIsPOST(): void
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
    public function usesServerForHeaderFromRawSource(): void
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
    public function usesCookieForCookieFromRawSource(): void
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
    public function returnsRequestMethodInUpperCase(): void
    {
        assertThat($this->webRequest->method(), equals('POST'));
    }

    /**
     * @test
     */
    public function sslCheckReturnsTrueIfHttpsSet(): void
    {
        assertTrue(
                $this->createBaseWebRequest([], ['HTTPS' => true])->isSsl()
        );
    }

    /**
     * @test
     */
    public function sslCheckReturnsFalseIfHttpsNotSet(): void
    {
        assertFalse(
                $this->createBaseWebRequest([], ['HTTPS' => null])->isSsl()
        );
    }

    /**
     * @since  2.0.2
     * @test
     */
    public function reportsVersion1_0WhenNoServerProtocolSet(): void
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
    public function reportsNullWhenServerProtocolContainsInvalidVersion(): void
    {
         assertNull(
                $this->createBaseWebRequest([], ['SERVER_PROTOCOL' => 'foo'])
                        ->protocolVersion()
        );
    }

    /**
     * @return  array<string[]>
     */
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
    public function reportsParsedProtocolVersion(string $protocol): void
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
    public function originatingIpAdressIsNullWhenAccordingHeadersNotPresent(): void
    {
        assertNull($this->createBaseWebRequest()->originatingIpAddress());
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAdressIsNullWhenRemoteAddressSyntacticallyInvalidAndNoForwardedForHeaderPresent(): void
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
    public function originatingIpAdressIsNullWhenForwardedForHeaderSyntacticallyInvalid(): void
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
    public function originatingIpAddressIsRemoteAddressWhenNoForwardedForHeaderPresent(): void
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
    public function originatingIpAddressIsInstanceOfIpAddress(): void
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
    public function originatingIpAddressIsForwardedAddressWhenForwardedForHeaderPresent(): void
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
    public function originatingIpAddressIsFirstFromForwardedAddressesWhenForwardedForHeaderContainsList(): void
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
    public function uriThrowsMalformedUriExceptionOnInvalidRequestUri(): void
    {
        expect(function() { $this->createBaseWebRequest([], [])->uri(); })
                ->throws(MalformedUri::class);
    }

    /**
     * @test
     */
    public function uriReturnsCompleteRequestUri(): void
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'stubbles.net',
                         'SERVER_PORT' => '80',
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
     * @since  8.0.1
     */
    public function uriWithInvalidServerPortIgnoresPort(): void
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'stubbles.net',
                         'SERVER_PORT' => 'abcd',
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                    )
                    ->uri()
                    ->asString(),
                equals('http://stubbles.net/index.php?foo=bar')
        );
    }

    /**
     * @test
     * @since  2.3.2
     */
    public function uriReturnsCompleteRequestUriWithoutDoublePortIfPortIsInHost(): void
    {
        assertThat(
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'localhost:8080',
                         'SERVER_PORT' => '80',
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
    public function uriReturnsCompleteRequestUriWithNonDefaultPort(): void
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
    public function uriReturnsCompleteRequestUriForHttps(): void
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
     * @group  upload
     * @since  8.1.0
     */
    public function uploadsProvidesAccessToUploadedFiles(): void
    {
        assertThat($this->webRequest->uploads(), isInstanceOf(Uploads::class));
    }

    /**
     * @test
     */
    public function returnsListOfParamNames(): void
    {
        assertThat($this->webRequest->paramNames(), equals(['foo', 'roland']));
    }

    /**
     * @test
     */
    public function returnsFalseOnCheckForNonExistingParam(): void
    {
        assertFalse($this->webRequest->hasParam('baz'));
    }

    /**
     * @test
     */
    public function returnsTrueOnCheckForExistingParam(): void
    {
        assertTrue($this->webRequest->hasParam('foo'));
    }

    /**
     * @test
     */
    public function validateParamReturnsValueValidator(): void
    {
        assertThat(
                $this->webRequest->validateParam('foo'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function validateParamReturnsValueValidatorForNonExistingParam(): void
    {
        assertThat(
                $this->webRequest->validateParam('baz'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function readParamReturnsValueReader(): void
    {
        assertThat(
                $this->webRequest->readParam('foo'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function readParamReturnsValueReaderForNonExistingParam(): void
    {
        assertThat(
                $this->webRequest->readParam('baz'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function returnsListOfHeaderNames(): void
    {
        assertThat(
                $this->webRequest->headerNames(),
                equals(['HTTP_ACCEPT', 'REQUEST_METHOD'])
        );
    }

    /**
     * @test
     */
    public function returnsHeaderErrors(): void
    {
        assertThat(
                $this->webRequest->headerErrors(),
                isInstanceOf(ParamErrors::class)
        );
    }

    /**
     * @test
     */
    public function returnsFalseOnCheckForNonExistingHeader(): void
    {
        assertFalse($this->webRequest->hasHeader('baz'));
    }

    /**
     * @test
     */
    public function returnsTrueOnCheckForExistingHeader(): void
    {
        assertTrue($this->webRequest->hasHeader('HTTP_ACCEPT'));
    }

    /**
     * @test
     * @since  3.1.1
     */
    public function returnsFalseOnCheckForRedirectHeaderWhenBothRedirectAndCurrentDoNotExist(): void
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertFalse($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    /**
     * @test
     * @since  3.1.1
     */
    public function returnsTrueOnCheckForRedirectHeaderWhenRedirectDoesNotButCurrentDoesExist(): void
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
    public function returnsTrueOnCheckForRedirectHeaderWhenBothRedirectAndCurrentExist(): void
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
    public function validateHeaderReturnsValueValidator(): void
    {
        assertThat(
                $this->webRequest->validateHeader('HTTP_ACCEPT'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function validateHeaderReturnsValueValidatorForNonExistingParam(): void
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
    public function validateRedirectHeaderReturnsValueValidatorForNonExistingHeader(): void
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
    public function validateRedirectHeaderReturnsValueValidatorWithOriginalHeaderIfRedirectHeaderNotPresent(): void
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
    public function validateRedirectHeaderReturnsValueValidatorWithRedirectHeaderIfRedirectHeaderPresent(): void
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
    public function readHeaderReturnsValueReader(): void
    {
        assertThat(
                $this->webRequest->readHeader('HTTP_ACCEPT'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function readHeaderReturnsValueReaderForNonExistingParam(): void
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
    public function readRedirectHeaderReturnsValueReaderForNonExistingHeader(): void
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
    public function readRedirectHeaderReturnsValueReaderWithOriginalHeaderIfRedirectHeaderNotPresent(): void
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
    public function readRedirectHeaderReturnsValueReaderWithRedirectHeaderIfRedirectHeaderPresent(): void
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
    public function returnsListOfCookieNames(): void
    {
        assertThat($this->webRequest->cookieNames(), equals(['chocolateChip', 'master']));
    }

    /**
     * @test
     */
    public function returnsCookieErrors(): void
    {
        assertThat(
                $this->webRequest->cookieErrors(),
                isInstanceOf(ParamErrors::class)
        );
    }

    /**
     * @test
     */
    public function returnsFalseOnCheckForNonExistingCookie(): void
    {
        assertFalse($this->webRequest->hasCookie('baz'));
    }

    /**
     * @test
     */
    public function returnsTrueOnCheckForExistingCookie(): void
    {
        assertTrue($this->webRequest->hasCookie('chocolateChip'));
    }

    /**
     * @test
     */
    public function validateCookieReturnsValueValidator(): void
    {
        assertThat(
                $this->webRequest->validateCookie('chocolateChip'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function validateCookieReturnsValueValidatorForNonExistingParam(): void
    {
        assertThat(
                $this->webRequest->validateCookie('baz'),
                isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @test
     */
    public function readCookieReturnsValueReader(): void
    {
        assertThat(
                $this->webRequest->readCookie('chocolateChip'),
                isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @test
     */
    public function readCookieReturnsValueReaderForNonExistingParam(): void
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
    public function returnsUserAgent(): void
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
    public function returnsUserAgentWhenHeaderNotPresent(): void
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
    public function userAgentDoesNotAcceptCookiesWhenNoCookiesInRequest(): void
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
    public function userAgentDoesNotRecognizeBotWithoutAdditionalSignature(): void
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
    public function userAgentRecognizedAsBotWithDefaultSignatures(): void
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
    public function userAgentRecognizedAsBotWithAdditionalSignature(): void
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
    public function generatesIdIfNoRequestIdHeaderPresent(): void
    {
        assertThat($this->createBaseWebRequest()->id(), isOfSize(25));
    }

    /**
     * @test
     * @group  request_id
     * @since  4.2.0
     */
    public function generatedIdIsPersistentThroughoutRequest(): void
    {
        $request = $this->createBaseWebRequest();
        assertThat($request->id(), equals($request->id()));
    }

    /**
     * @return  array<mixed[]>
     */
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
    public function generatesIdIfRequestContainsInvalidValue(string $invalidValue): void
    {
        assertThat(
                $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $invalidValue])->id(),
                isNotEqualTo($invalidValue)
        );
    }

    /**
     * @return  array<mixed[]>
     */
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
    public function returnsValidValueFromHeader(string $validValue): void
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
    public function bodyReturnsInputStream(): void
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
    public function hasNoSessionAttachedByDefault(): void
    {
        assertFalse($this->createBaseWebRequest()->hasSessionAttached());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function defaultSessionIsNull(): void
    {
        assertNull($this->createBaseWebRequest()->attachedSession());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function hasSessionWhenAttached(): void
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
    public function returnsAttachedSession(): void
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
    public function hasNoIdentityAssociatedByDefault(): void
    {
        assertFalse($this->createBaseWebRequest()->hasAssociatedIdentity());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function defaultIdentityIsNull(): void
    {
        assertNull($this->createBaseWebRequest()->identity());
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function hasIdentityWhenAssociated(): void
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
    public function returnsAssociatedIdentity(): void
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

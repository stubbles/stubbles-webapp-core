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
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use stubbles\input\{ValueReader, ValueValidator, errors\ParamErrors};
use stubbles\peer\{IpAddress, MalformedUri, http\Http, http\HttpVersion};
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
 */
#[Group('request')]
class WebRequestTest extends TestCase
{
    private WebRequest $webRequest;
    /**
     * backup of globals $_GET, $_POST, $_SERVER, $COOKIE
     *
     * @var  array<string,array<string,string>>
     */
    private array $globals;

    protected function setUp(): void
    {
        $this->globals = [
            'GET'    => $_GET,
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
     * @param  array<string,scalar|null>  $params
     * @param  array<string,scalar|null>  $headers
     * @param  array<string,scalar|null>  $cookies
     */
    private function createBaseWebRequest(
        array $params  = [],
        array $headers = [],
        array $cookies = []
    ): WebRequest {
        return new WebRequest($params, $headers, $cookies);
    }

    private function fillGlobals(string $requestMethod = Http::GET): void
    {
        $_GET    = ['foo' => 'bar', 'roland' => 'TB-303'];
        $_POST   = ['baz' => 'blubb', 'donald' => '313'];
        $_SERVER = ['REQUEST_METHOD' => $requestMethod, 'HTTP_ACCEPT' => 'text/html'];
        $_COOKIE = ['chocolateChip'  => 'Omnomnomnom', 'master' => 'servant'];
    }

    #[Test]
    public function usesGetParamsFromRawSourceWhenRequestMethodIsGET(): void
    {
        $this->fillGlobals(Http::GET);
        assertThat(
            WebRequest::fromRawSource()->paramNames(),
            equals(['foo', 'roland'])
        );
    }

    #[Test]
    public function usesPostParamsFromRawSourceWhenRequestMethodIsPOST(): void
    {
        $this->fillGlobals(Http::POST);
        assertThat(
            WebRequest::fromRawSource()->paramNames(),
            equals(['baz', 'donald'])
        );
    }

    #[Test]
    public function usesServerForHeaderFromRawSource(): void
    {
        $this->fillGlobals();
        assertThat(
            WebRequest::fromRawSource()->headerNames(),
            equals(['REQUEST_METHOD', 'HTTP_ACCEPT'])
        );
    }

    #[Test]
    public function usesCookieForCookieFromRawSource(): void
    {
        $this->fillGlobals();
        assertThat(
            WebRequest::fromRawSource()->cookieNames(),
            equals(['chocolateChip', 'master'])
        );
    }

    #[Test]
    public function returnsRequestMethodInUpperCase(): void
    {
        assertThat($this->webRequest->method(), equals(Http::POST));
    }

    /**
     * @since  9.1.0
     */
    #[Test]
    #[Group('supplanted_request_method')]
    #[TestWith([Http::PUT])]
    #[TestWith([Http::DELETE])]
    public function returnsSupplantedRequestMethodWhenSetViaParam(string $requestMethod): void
    {
        $request = $this->createBaseWebRequest(
            ['_method' => $requestMethod],
            ['REQUEST_METHOD' => Http::POST]
        );
        assertThat($request->method(), equals($requestMethod));
    }

    /**
     * @since  9.1.0
     */
    #[Test]
    #[Group('supplanted_request_method')]
    public function returnsOriginalRequestMethodOnInvalidSupplantedRequestMethod(): void
    {
        $request = $this->createBaseWebRequest(
            ['_method' => 'NOPE'],
            ['REQUEST_METHOD' => Http::POST]
        );
        assertThat($request->method(), equals(Http::POST));
    }

    /**
     * @since  9.1.0
     */
    #[Test]
    #[Group('supplanted_request_method')]
    #[TestWith([Http::GET])]
    #[TestWith([Http::HEAD])]
    #[TestWith([Http::OPTIONS])]
    #[TestWith([Http::DELETE])]
    public function returnsOriginalRequestMethodOnAnyRequestMethodOtherThanPOST(
        string $requestMethod
    ): void {
        $request = $this->createBaseWebRequest(
            ['_method' => Http::PUT],
            ['REQUEST_METHOD' => $requestMethod]
        );
        assertThat($request->method(), equals($requestMethod));
    }

    #[Test]
    public function sslCheckReturnsTrueIfHttpsSet(): void
    {
        assertTrue(
            $this->createBaseWebRequest([], ['HTTPS' => true])->isSsl()
        );
    }

    #[Test]
    public function sslCheckReturnsFalseIfHttpsNotSet(): void
    {
        assertFalse(
            $this->createBaseWebRequest([], ['HTTPS' => null])->isSsl()
        );
    }

    /**
     * @since  2.0.2
     */
    #[Test]
    public function reportsVersion1_0WhenNoServerProtocolSet(): void
    {
         assertThat(
            $this->createBaseWebRequest([], [])->protocolVersion(),
            equals(HttpVersion::HTTP_1_0)
        );
    }

    /**
     * @since  2.0.2
     */
    #[Test]
    public function reportsNullWhenServerProtocolContainsInvalidVersion(): void
    {
         assertNull(
            $this->createBaseWebRequest([], ['SERVER_PROTOCOL' => 'foo'])
                ->protocolVersion()
        );
    }

    /**
     * @since  3.0.0
     */
    #[Test]
    #[TestWith(['HTTP/0.9'])]
    #[TestWith([HttpVersion::HTTP_1_0])]
    #[TestWith([HttpVersion::HTTP_1_1])]
    #[TestWith(['HTTP/1.2'])]
    #[TestWith(['HTTP/1.12'])]
    #[TestWith(['HTTP/2.0'])]
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
     */
    #[Test]
    public function originatingIpAdressIsNullWhenAccordingHeadersNotPresent(): void
    {
        assertNull($this->createBaseWebRequest()->originatingIpAddress());
    }

    /**
     * @since  3.0.0
     */
    #[Test]
    public function originatingIpAdressIsNullWhenRemoteAddressSyntacticallyInvalidAndNoForwardedForHeaderPresent(): void
    {
        assertNull(
            $this->createBaseWebRequest([], ['REMOTE_ADDR' => 'foo'])
                ->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     */
    #[Test]
    public function originatingIpAdressIsNullWhenForwardedForHeaderSyntacticallyInvalid(): void
    {
        assertNull(
            $this->createBaseWebRequest(
                [],
                [
                    'REMOTE_ADDR'          => '127.0.0.1',
                    'HTTP_X_FORWARDED_FOR' => 'foo'
                ]
            )->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     */
    #[Test]
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
     */
    #[Test]
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
     */
    #[Test]
    public function originatingIpAddressIsForwardedAddressWhenForwardedForHeaderPresent(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                [],
                [
                    'REMOTE_ADDR'          => '127.0.0.1',
                    'HTTP_X_FORWARDED_FOR' => '172.19.120.123'
                ]
            )->originatingIpAddress(),
            equals('172.19.120.123')
        );
    }

    /**
     * @since  3.0.0
     */
    #[Test]
    public function originatingIpAddressIsFirstFromForwardedAddressesWhenForwardedForHeaderContainsList(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                [],
                [
                    'REMOTE_ADDR'          => '127.0.0.1',
                    'HTTP_X_FORWARDED_FOR' => '172.19.120.122, 168.30.48.124'
                ]
            )->originatingIpAddress(),
            equals('172.19.120.122')
        );
    }

    #[Test]
    public function uriThrowsMalformedUriExceptionOnInvalidRequestUri(): void
    {
        expect(function() { $this->createBaseWebRequest([], [])->uri(); })
            ->throws(MalformedUri::class);
    }

    #[Test]
    public function uriReturnsCompleteRequestUri(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                ['foo'         => 'bar'],
                [
                    'HTTPS'       => null,
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
     * @since  8.0.1
     */
    #[Test]
    public function uriWithInvalidServerPortIgnoresPort(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                ['foo'         => 'bar'],
                [
                    'HTTPS'       => null,
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
     * @since  2.3.2
     */
    #[Test]
    public function uriReturnsCompleteRequestUriWithoutDoublePortIfPortIsInHost(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                ['foo'         => 'bar'],
                [
                    'HTTPS'       => null,
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
     * @since  2.3.2
     */
    #[Test]
    public function uriReturnsCompleteRequestUriWithNonDefaultPort(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                ['foo'         => 'bar'],
                [
                    'HTTPS'       => null,
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

    #[Test]
    public function uriReturnsCompleteRequestUriForHttps(): void
    {
        assertThat(
            $this->createBaseWebRequest(
                ['foo'         => 'bar'],
                [
                    'HTTPS'       => true,
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
     * @since  8.1.0
     */
    #[Test]
    #[Group('upload')]
    public function uploadsProvidesAccessToUploadedFiles(): void
    {
        assertThat($this->webRequest->uploads(), isInstanceOf(Uploads::class));
    }

    #[Test]
    public function returnsListOfParamNames(): void
    {
        assertThat($this->webRequest->paramNames(), equals(['foo', 'roland']));
    }

    #[Test]
    public function returnsFalseOnCheckForNonExistingParam(): void
    {
        assertFalse($this->webRequest->hasParam('baz'));
    }

    #[Test]
    public function returnsTrueOnCheckForExistingParam(): void
    {
        assertTrue($this->webRequest->hasParam('foo'));
    }

    #[Test]
    public function validateParamReturnsValueValidator(): void
    {
        assertThat(
                $this->webRequest->validateParam('foo'),
                isInstanceOf(ValueValidator::class)
        );
    }

    #[Test]
    public function validateParamReturnsValueValidatorForNonExistingParam(): void
    {
        assertThat(
            $this->webRequest->validateParam('baz'),
            isInstanceOf(ValueValidator::class)
        );
    }

    #[Test]
    public function readParamReturnsValueReader(): void
    {
        assertThat(
            $this->webRequest->readParam('foo'),
            isInstanceOf(ValueReader::class)
        );
    }

    #[Test]
    public function readParamReturnsValueReaderForNonExistingParam(): void
    {
        assertThat(
            $this->webRequest->readParam('baz'),
            isInstanceOf(ValueReader::class)
        );
    }

    #[Test]
    public function returnsListOfHeaderNames(): void
    {
        assertThat(
            $this->webRequest->headerNames(),
            equals(['HTTP_ACCEPT', 'REQUEST_METHOD'])
        );
    }

    #[Test]
    public function returnsHeaderErrors(): void
    {
        assertThat(
            $this->webRequest->headerErrors(),
            isInstanceOf(ParamErrors::class)
        );
    }

    #[Test]
    public function returnsFalseOnCheckForNonExistingHeader(): void
    {
        assertFalse($this->webRequest->hasHeader('baz'));
    }

    #[Test]
    public function returnsTrueOnCheckForExistingHeader(): void
    {
        assertTrue($this->webRequest->hasHeader('HTTP_ACCEPT'));
    }

    /**
     * @since  3.1.1
     */
    #[Test]
    public function returnsFalseOnCheckForRedirectHeaderWhenBothRedirectAndCurrentDoNotExist(): void
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertFalse($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    /**
     * @since  3.1.1
     */
    #[Test]
    public function returnsTrueOnCheckForRedirectHeaderWhenRedirectDoesNotButCurrentDoesExist(): void
    {
        $webRequest = $this->createBaseWebRequest(
            [],
            ['HTTP_AUTHORIZATION' => 'someCoolToken']
        );
        assertTrue($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    /**
     * @since  3.1.1
     */
    #[Test]
    public function returnsTrueOnCheckForRedirectHeaderWhenBothRedirectAndCurrentExist(): void
    {
        $webRequest = $this->createBaseWebRequest(
            [],
            [
                'HTTP_AUTHORIZATION'          => 'someCoolToken',
                'REDIRECT_HTTP_AUTHORIZATION' => 'realToken'
            ]
        );
        assertTrue($webRequest->hasRedirectHeader('HTTP_AUTHORIZATION'));
    }

    #[Test]
    public function validateHeaderReturnsValueValidator(): void
    {
        assertThat(
            $this->webRequest->validateHeader('HTTP_ACCEPT'),
            isInstanceOf(ValueValidator::class)
        );
    }

    #[Test]
    public function validateHeaderReturnsValueValidatorForNonExistingParam(): void
    {
        assertThat(
            $this->webRequest->validateHeader('baz'),
            isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('redirect_header')]
    public function validateRedirectHeaderReturnsValueValidatorForNonExistingHeader(): void
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertThat(
            $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION'),
            isInstanceOf(ValueValidator::class)
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('redirect_header')]
    public function validateRedirectHeaderReturnsValueValidatorWithOriginalHeaderIfRedirectHeaderNotPresent(): void
    {
        $webRequest = $this->createBaseWebRequest([], ['HTTP_AUTHORIZATION' => 'someCoolToken']);
        assertTrue(
            $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION')->isEqualTo('someCoolToken')
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('redirect_header')]
    public function validateRedirectHeaderReturnsValueValidatorWithRedirectHeaderIfRedirectHeaderPresent(): void
    {
        $webRequest = $this->createBaseWebRequest(
            [],
            [
                'HTTP_AUTHORIZATION'          => 'someCoolToken',
                'REDIRECT_HTTP_AUTHORIZATION' => 'realToken'
            ]
        );
        assertTrue(
            $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION')->isEqualTo('realToken')
        );
    }

    #[Test]
    public function readHeaderReturnsValueReader(): void
    {
        assertThat(
            $this->webRequest->readHeader('HTTP_ACCEPT'),
            isInstanceOf(ValueReader::class)
        );
    }

    #[Test]
    public function readHeaderReturnsValueReaderForNonExistingParam(): void
    {
        assertThat(
            $this->webRequest->readHeader('baz'),
            isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('redirect_header')]
    public function readRedirectHeaderReturnsValueReaderForNonExistingHeader(): void
    {
        $webRequest = $this->createBaseWebRequest([], []);
        assertNull(
            $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure()
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('redirect_header')]
    public function readRedirectHeaderReturnsValueReaderWithOriginalHeaderIfRedirectHeaderNotPresent(): void
    {
        $webRequest = $this->createBaseWebRequest([], ['HTTP_AUTHORIZATION' => 'someCoolToken']);
        assertThat(
            $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure(),
            equals('someCoolToken')
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('redirect_header')]
    public function readRedirectHeaderReturnsValueReaderWithRedirectHeaderIfRedirectHeaderPresent(): void
    {
        $webRequest = $this->createBaseWebRequest(
            [],
            [
                'HTTP_AUTHORIZATION'          => 'someCoolToken',
                'REDIRECT_HTTP_AUTHORIZATION' => 'realToken'
            ]
        );
        assertThat(
            $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure(),
            equals('realToken')
        );
    }

    #[Test]
    public function returnsListOfCookieNames(): void
    {
        assertThat($this->webRequest->cookieNames(), equals(['chocolateChip', 'master']));
    }

    #[Test]
    public function returnsCookieErrors(): void
    {
        assertThat(
            $this->webRequest->cookieErrors(),
            isInstanceOf(ParamErrors::class)
        );
    }

    #[Test]
    public function returnsFalseOnCheckForNonExistingCookie(): void
    {
        assertFalse($this->webRequest->hasCookie('baz'));
    }

    #[Test]
    public function returnsTrueOnCheckForExistingCookie(): void
    {
        assertTrue($this->webRequest->hasCookie('chocolateChip'));
    }

    #[Test]
    public function validateCookieReturnsValueValidator(): void
    {
        assertThat(
            $this->webRequest->validateCookie('chocolateChip'),
            isInstanceOf(ValueValidator::class)
        );
    }

    #[Test]
    public function validateCookieReturnsValueValidatorForNonExistingParam(): void
    {
        assertThat(
            $this->webRequest->validateCookie('baz'),
            isInstanceOf(ValueValidator::class)
        );
    }

    #[Test]
    public function readCookieReturnsValueReader(): void
    {
        assertThat(
            $this->webRequest->readCookie('chocolateChip'),
            isInstanceOf(ValueReader::class)
        );
    }

    #[Test]
    public function readCookieReturnsValueReaderForNonExistingParam(): void
    {
        assertThat(
            $this->webRequest->readCookie('baz'),
            isInstanceOf(ValueReader::class)
        );
    }

    /**
     * @since  4.1.0
     */
    #[Test]
    #[Group('issue_65')]
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
     */
    #[Test]
    #[Group('issue_65')]
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
     */
    #[Test]
    #[Group('issue_65')]
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
     */
    #[Test]
    #[Group('issue_65')]
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
     */
    #[Test]
    #[Group('issue_65')]
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
     */
    #[Test]
    #[Group('issue_65')]
    public function userAgentRecognizedAsBotWithAdditionalSignature(): void
    {
        assertTrue(
            $this->createBaseWebRequest([], ['HTTP_USER_AGENT' => 'foo'], [])
                ->userAgent(['foo' => '~foo~'])
                ->isBot()
        );
    }

    /**
     * @since  4.2.0
     */
    #[Test]
    #[Group('request_id')]
    public function generatesIdIfNoRequestIdHeaderPresent(): void
    {
        assertThat($this->createBaseWebRequest()->id(), isOfSize(25));
    }

    /**
     * @since  4.2.0
     */
    #[Test]
    #[Group('request_id')]
    public function generatedIdIsPersistentThroughoutRequest(): void
    {
        $request = $this->createBaseWebRequest();
        assertThat($request->id(), equals($request->id()));
    }

    public static function invalidRequestIdValues(): Generator
    {
        yield ['too-short'];
        yield [str_pad('too-long', 201, '-')];
        yield ['invalid character like space'];
        yield ["valid-but-\n-linebreaks"];
    }

    /**
     * @since  4.2.0
     */
    #[Test]
    #[Group('request_id')]
    #[DataProvider('invalidRequestIdValues')]
    public function generatesIdIfRequestContainsInvalidValue(string $invalidValue): void
    {
        assertThat(
            $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $invalidValue])->id(),
            isNotEqualTo($invalidValue)
        );
    }

    public static function validRequestIdValues(): Generator
    {
        yield [str_pad('minimum-size', 20, '-')];
        yield [str_pad('max-size', 200, '-')];
        yield ['valid-characters-like+and/numbers=21903'];
    }

    /**
     * @since  4.2.0
     */
    #[Test]
    #[Group('request_id')]
    #[DataProvider('validRequestIdValues')]
    public function returnsValidValueFromHeader(string $validValue): void
    {
        assertThat(
            $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $validValue])->id(),
            equals($validValue)
        );
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function bodyReturnsInputStream(): void
    {
        assertThat(
            $this->createBaseWebRequest()->body(),
            isInstanceOf(InputStream::class)
        );
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function hasNoSessionAttachedByDefault(): void
    {
        assertFalse($this->createBaseWebRequest()->hasSessionAttached());
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function defaultSessionIsNull(): void
    {
        assertNull($this->createBaseWebRequest()->attachedSession());
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function hasSessionWhenAttached(): void
    {
        $request = $this->createBaseWebRequest();
        $session = NewInstance::of(Session::class);
        $request->attachSession($session);
        assertTrue($request->hasSessionAttached());
    }

    /**
     * @since  6.0.0
     */
    #[Test]
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
     * @since  6.0.0
     */
    #[Test]
    public function hasNoIdentityAssociatedByDefault(): void
    {
        assertFalse($this->createBaseWebRequest()->hasAssociatedIdentity());
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function defaultIdentityIsNull(): void
    {
        assertNull($this->createBaseWebRequest()->identity());
    }

    /**
     * @since  6.0.0
     */
    #[Test]
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
     * @since  6.0.0
     */
    #[Test]
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

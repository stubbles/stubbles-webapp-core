<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\request;
use stubbles\peer\http\HttpVersion;
use stubbles\webapp\auth\Identity;
use stubbles\webapp\auth\Roles;
/**
 * Tests for stubbles\webapp\request\WebRequest.
 *
 * @group  request
 */
class WebRequestTest extends \PHPUnit_Framework_TestCase
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

    /**
     * set up test environment
     */
    public function setUp()
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

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        $_GET    = $this->globals['GET'];
        $_POST   = $this->globals['POST'];
        $_SERVER = $this->globals['SERVER'];
        $_COOKIE = $this->globals['COOKIE'];
    }

    /**
     * creates instance to test
     *
     * @param   array  $params   optional
     * @param   array  $headers  optional
     * @param   array  $cookies  optional
     * @return  WebRequest
     */
    private function createBaseWebRequest(array $params = [], array $headers = [], array $cookies = [])
    {
        return new WebRequest($params, $headers, $cookies);
    }

    /**
     * helper method to fill globals with data
     *
     * @param  string  $requestMethod
     */
    private function fillGlobals($requestMethod = 'GET')
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
        assertEquals(
                ['foo', 'roland'],
                WebRequest::fromRawSource()->paramNames()
        );
    }

    /**
     * @test
     */
    public function usesPostParamsFromRawSourceWhenRequestMethodIsPOST()
    {
        $this->fillGlobals('POST');
        assertEquals(
                ['baz', 'donald'],
                WebRequest::fromRawSource()->paramNames()
        );
    }

    /**
     * @test
     */
    public function usesServerForHeaderFromRawSource()
    {
        $this->fillGlobals();
        assertEquals(
                ['REQUEST_METHOD', 'HTTP_ACCEPT'],
                WebRequest::fromRawSource()->headerNames()
        );
    }

    /**
     * @test
     */
    public function usesCookieForCookieFromRawSource()
    {
        $this->fillGlobals();
        assertEquals(
                ['chocolateChip', 'master'],
                WebRequest::fromRawSource()->cookieNames()
        );
    }

    /**
     * @test
     */
    public function returnsRequestMethodInUpperCase()
    {
        assertEquals('POST', $this->webRequest->method());
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
         assertEquals(
                 HttpVersion::HTTP_1_0,
                $this->createBaseWebRequest([], [])->protocolVersion()
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

    /**
     * @return  array
     */
    public function protocolVersions()
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
    public function reportsParsedProtocolVersion($protocol)
    {
         assertEquals(
                HttpVersion::fromString($protocol),
                $this->createBaseWebRequest([], ['SERVER_PROTOCOL' => $protocol])
                        ->protocolVersion()
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
        assertEquals(
                '127.0.0.1',
                $this->createBaseWebRequest([], ['REMOTE_ADDR' => '127.0.0.1'])
                     ->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsInstanceOfIpAddress()
    {
        assertInstanceOf(
                'stubbles\peer\IpAddress',
                $this->createBaseWebRequest([], ['REMOTE_ADDR' => '127.0.0.1'])
                     ->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsForwardedAddressWhenForwardedForHeaderPresent()
    {
        assertEquals(
                '172.19.120.122',
                $this->createBaseWebRequest(
                        [],
                        ['REMOTE_ADDR'          => '127.0.0.1',
                         'HTTP_X_FORWARDED_FOR' => '172.19.120.122'
                        ]
                       )
                     ->originatingIpAddress()
        );
    }

    /**
     * @since  3.0.0
     * @test
     */
    public function originatingIpAddressIsFirstFromForwardedAddressesWhenForwardedForHeaderContainsList()
    {
        assertEquals(
                '172.19.120.122',
                $this->createBaseWebRequest(
                        [],
                        ['REMOTE_ADDR'          => '127.0.0.1',
                         'HTTP_X_FORWARDED_FOR' => '172.19.120.122, 168.30.48.124'
                        ]
                       )
                     ->originatingIpAddress()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\peer\MalformedUriException
     */
    public function uriThrowsMalformedUriExceptionOnInvalidRequestUri()
    {
        $this->createBaseWebRequest([], [])->uri();
    }

    /**
     * @test
     */
    public function uriReturnsCompleteRequestUri()
    {
        assertEquals(
                'http://stubbles.net:80/index.php?foo=bar',
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'stubbles.net',
                         'SERVER_PORT' => 80,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                )
                ->uri()
                ->asString()
        );
    }

    /**
     * @test
     * @since  2.3.2
     */
    public function uriReturnsCompleteRequestUriWithoutDoublePortIfPortIsInHost()
    {
        assertEquals(
                'http://localhost:8080/index.php?foo=bar',
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'localhost:8080',
                         'SERVER_PORT' => 80,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                )
                ->uri()
                ->asString()
        );
    }

    /**
     * @test
     * @since  2.3.2
     */
    public function uriReturnsCompleteRequestUriWithNonDefaultPort()
    {
        assertEquals(
                'http://example.net:8080/index.php?foo=bar',
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => null,
                         'HTTP_HOST'   => 'example.net',
                         'SERVER_PORT' => 8080,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                )
                ->uri()
                ->asString()
        );
    }

    /**
     * @test
     */
    public function uriReturnsCompleteRequestUriForHttps()
    {
        assertEquals(
                'https://stubbles.net:443/index.php?foo=bar',
                $this->createBaseWebRequest(
                        ['foo'         => 'bar'],
                        ['HTTPS'       => true,
                         'HTTP_HOST'   => 'stubbles.net',
                         'SERVER_PORT' => 443,
                         'REQUEST_URI' => '/index.php?foo=bar'
                        ]
                )
                ->uri()
                ->asString()
        );
    }

    /**
     * @test
     */
    public function returnsListOfParamNames()
    {
        assertEquals(
                ['foo', 'roland'],
                $this->webRequest->paramNames()
        );
    }

    /**
     * @test
     */
    public function returnsParamErrors()
    {
        assertInstanceOf(
                'stubbles\input\errors\ParamErrors',
                $this->webRequest->paramErrors()
        );
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
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $this->webRequest->validateParam('foo')
        );
    }

    /**
     * @test
     */
    public function validateParamReturnsValueValidatorForNonExistingParam()
    {
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $this->webRequest->validateParam('baz')
        );
    }

    /**
     * @test
     */
    public function readParamReturnsValueReader()
    {
        assertInstanceOf(
                'stubbles\input\ValueReader',
                $this->webRequest->readParam('foo')
        );
    }

    /**
     * @test
     */
    public function readParamReturnsValueReaderForNonExistingParam()
    {
        assertInstanceOf(
                'stubbles\input\ValueReader',
                $this->webRequest->readParam('baz')
        );
    }

    /**
     * @test
     */
    public function returnsListOfHeaderNames()
    {
        assertEquals(
                ['HTTP_ACCEPT', 'REQUEST_METHOD'],
                $this->webRequest->headerNames()
        );
    }

    /**
     * @test
     */
    public function returnsHeaderErrors()
    {
        assertInstanceOf(
                'stubbles\input\errors\ParamErrors',
                $this->webRequest->headerErrors()
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
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $this->webRequest->validateHeader('HTTP_ACCEPT')
        );
    }

    /**
     * @test
     */
    public function validateHeaderReturnsValueValidatorForNonExistingParam()
    {
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $this->webRequest->validateHeader('baz')
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
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $webRequest->validateRedirectHeader('HTTP_AUTHORIZATION')
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
        assertInstanceOf(
                'stubbles\input\ValueReader',
                $this->webRequest->readHeader('HTTP_ACCEPT')
        );
    }

    /**
     * @test
     */
    public function readHeaderReturnsValueReaderForNonExistingParam()
    {
        assertInstanceOf(
                'stubbles\input\ValueReader',
                $this->webRequest->readHeader('baz')
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
        assertEquals(
                'someCoolToken',
                $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure()
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
        assertEquals(
                'realToken',
                $webRequest->readRedirectHeader('HTTP_AUTHORIZATION')->unsecure()
        );
    }

    /**
     * @test
     */
    public function returnsListOfCookieNames()
    {
        assertEquals(
                ['chocolateChip', 'master'],
                $this->webRequest->cookieNames()
        );
    }

    /**
     * @test
     */
    public function returnsCookieErrors()
    {
        assertInstanceOf(
                'stubbles\input\errors\ParamErrors',
                $this->webRequest->cookieErrors()
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
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $this->webRequest->validateCookie('chocolateChip')
        );
    }

    /**
     * @test
     */
    public function validateCookieReturnsValueValidatorForNonExistingParam()
    {
        assertInstanceOf(
                'stubbles\input\ValueValidator',
                $this->webRequest->validateCookie('baz')
        );
    }

    /**
     * @test
     */
    public function readCookieReturnsValueReader()
    {
        assertInstanceOf(
                'stubbles\input\ValueReader',
                $this->webRequest->readCookie('chocolateChip')
        );
    }

    /**
     * @test
     */
    public function readCookieReturnsValueReaderForNonExistingParam()
    {
        assertInstanceOf(
                'stubbles\input\ValueReader',
                $this->webRequest->readCookie('baz')
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function returnsUserAgent()
    {
        assertEquals(
                new UserAgent('foo', true),
                $this->createBaseWebRequest(
                        [],
                        ['HTTP_USER_AGENT' => 'foo'],
                        ['chocolateChip' => 'someValue']
                )->userAgent()
        );
    }

    /**
     * @since  4.1.0
     * @test
     * @group  issue_65
     */
    public function returnsUserAgentWhenHeaderNotPresent()
    {
        assertEquals(
                new UserAgent(null, true),
                $this->createBaseWebRequest(
                        [],
                        [],
                        ['chocolateChip' => 'someValue']
                )->userAgent()
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
        assertEquals(
                25,
                strlen($this->createBaseWebRequest()->id()),
                'Expected a generated id with 25 characters'
        );
    }

    /**
     * @test
     * @group  request_id
     * @since  4.2.0
     */
    public function generatedIdIsPersistentThroughoutRequest()
    {
        $request = $this->createBaseWebRequest();
        assertEquals(
                $request->id(),
                $request->id()
        );
    }

    /**
     * @return  array
     */
    public function invalidRequestIdValues()
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
    public function generatesIdIfRequestContainsInvalidValue($invalidValue)
    {
        assertNotEquals(
                $invalidValue,
                $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $invalidValue])->id()
        );
    }

    /**
     * @return  array
     */
    public function validRequestIdValues()
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
    public function returnsValidValueFromHeader($validValue)
    {
        assertEquals(
                $validValue,
                $this->createBaseWebRequest([], ['HTTP_X_REQUEST_ID' => $validValue])->id()
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function bodyReturnsInputStream()
    {
        assertInstanceOf(
                'stubbles\streams\InputStream',
                $this->createBaseWebRequest()->body()
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
        $session = $this->getMock('stubbles\webapp\session\Session');
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
        $session = $this->getMock('stubbles\webapp\session\Session');
        assertSame(
                $request->attachSession($session),
                $request->attachedSession()
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
        $identity = new Identity(
                $this->getMock('stubbles\webapp\auth\User'), Roles::none()
        );
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
        $identity = new Identity(
                $this->getMock('stubbles\webapp\auth\User'), Roles::none()
        );
        assertSame(
                $identity,
                $this->createBaseWebRequest()
                        ->associate($identity)
                        ->identity()
        );
    }
}
